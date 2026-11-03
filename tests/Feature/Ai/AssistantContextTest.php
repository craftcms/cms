<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\Content\ElementContexts;
use CraftCms\Cms\Ai\Assistant\ShowComponents;
use CraftCms\Cms\Ai\Assistant\ShowElements;
use CraftCms\Cms\Ai\Assistant\ShowField;
use CraftCms\Cms\Ai\AssistantContextTypes;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Tests\TestClasses\Ai\TestOrderContext;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request as ToolRequest;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    Edition::set(Edition::Pro);
    actingAs(User::findOne());

    $field = Field::factory()->create(['name' => 'Summary', 'handle' => 'summary', 'type' => PlainText::class]);
    $entryType = EntryType::factory()->withField($field)->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $this->entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Lighthouses']);
    $this->entry->setFieldValue('summary', 'Saved summary');
    Elements::saveElement($this->entry);
});

/**
 * Sends a message with the entry in its context, as the user has it in their editor.
 *
 * @param  list<string>|null  $fields
 */
function chatAboutEntry(Entry $entry, ?array $fields = null, ?string $threadId = null): TestResponse
{
    return postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId ?? (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Improve this']],
        'forwardedProps' => ['context' => entryContext($entry, $fields)],
    ]);
}

/**
 * The context of a message about an entry, as the editor has it, with unsaved changes.
 *
 * @return list<array<string, mixed>>
 */
function entryContext(Entry $entry, ?array $fields = null): array
{
    return [[
        'type' => 'craft:element',
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'ref' => 'ref-1',
        'namespace' => [],
        'values' => ['title' => 'Lighthouses', 'fields' => ['summary' => 'Unsaved summary']],
        'fields' => $fields,
    ]];
}

/**
 * @return array<string, mixed>
 */
function proposeChangesResult(): array
{
    return Json::decode(ConversationMessage::query()->where('role', 'assistant')->sole()->tool_results[0]['result']);
}

it('proposes changes to an element in the context, as the user has it in their editor, without saving them', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $this->entry->id, 'fields' => ['summary' => 'New summary']]),
        'Shortened the summary.',
    ]);

    chatAboutEntry($this->entry)->assertOk()->streamedContent();

    expect(proposeChangesResult()['proposals'])->toHaveCount(1)
        ->and(proposeChangesResult()['proposals'][0])->toMatchArray([
            'key' => 'e1:fields.summary',
            'ref' => 'ref-1',
            'path' => ['fields', 'summary'],
            'value' => 'New summary',
            'diff' => ['current' => 'Unsaved summary', 'proposed' => 'New summary'],
        ])
        ->and(Entry::find()->id($this->entry->id)->one()->getFieldValue('summary'))->toBe('Saved summary');
});

it('proposes a change between values PHP would loosely compare as equal', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $this->entry->id, 'fields' => ['summary' => '1e1']]),
        'Changed it.',
    ]);
    $context = entryContext($this->entry);
    $context[0]['values']['fields']['summary'] = '10';

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Improve this']],
        'forwardedProps' => ['context' => $context],
    ])->assertOk()->streamedContent();

    expect(proposeChangesResult()['proposals'])->toHaveCount(1)
        ->and(proposeChangesResult()['proposals'][0]['diff'])->toBe(['current' => '10', 'proposed' => '1e1']);
});

it('proposes changes to an element an earlier message was sent with, as its editor last saved it', function () {
    AssistantAgent::fake([
        'Noted.',
        new ToolCall('call_1', 'propose_changes', ['id' => $this->entry->id, 'fields' => ['summary' => 'Short']]),
        'Shortened it.',
    ]);
    $threadId = (string) Str::uuid();
    $reference = [
        'type' => 'craft:element',
        'ref' => 'ref-1',
        'label' => 'Lighthouses',
        'data' => ['elementType' => Entry::class, 'elementId' => $this->entry->id, 'siteId' => $this->entry->siteId, 'namespace' => [], 'fields' => null],
    ];
    $send = fn (string $content, array $context) => postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $content]],
        'forwardedProps' => ['context' => $context],
    ])->assertOk()->streamedContent();

    $send('<context>'.Json::encode([$reference])."</context>\n\nRead this", entryContext($this->entry));
    $send('Make the summary shorter', []);

    $result = Json::decode(ConversationMessage::query()->where('role', 'assistant')->latest()->orderByDesc('id')->firstOrFail()->tool_results[0]['result']);

    expect($result['proposals'][0])->toMatchArray([
        'ref' => 'ref-1',
        'diff' => ['current' => 'Saved summary', 'proposed' => 'Short'],
    ]);
});

it('names the element open in the user’s editor without its content, until the model reads it as they have it there', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'read_editor_element', []),
        new ToolCall('call_2', 'propose_changes', ['id' => $this->entry->id, 'fields' => ['summary' => 'New summary']]),
        'Shortened the summary.',
    ]);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Shorten the summary']],
        'forwardedProps' => ['context' => [[...entryContext($this->entry)[0], 'ref' => 'editor-1', 'open' => true]]],
    ])->assertOk()->streamedContent();

    $results = ConversationMessage::query()->where('role', 'assistant')->sole()->tool_results;

    expect($results[0]['result'])->toContain('"summary":"Unsaved summary"')
        ->and(Json::decode($results[1]['result'])['proposals'][0])->toMatchArray([
            'ref' => 'editor-1',
            'diff' => ['current' => 'Unsaved summary', 'proposed' => 'New summary'],
        ]);
    $id = $this->entry->id;

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), "the entry “Lighthouses” (ID $id), which the user has open in their editor")
        && ! str_contains($prompt->agent->instructions(), 'Unsaved summary'));
});

it('names the element open in the user’s editor in place of what an earlier message was sent with of it', function () {
    AssistantAgent::fake(['Noted.', 'Done.']);
    $threadId = (string) Str::uuid();
    $reference = [
        'type' => 'craft:element',
        'ref' => 'ref-1',
        'label' => 'Lighthouses',
        'data' => ['elementType' => Entry::class, 'elementId' => $this->entry->id, 'siteId' => $this->entry->siteId, 'namespace' => [], 'fields' => null],
    ];
    $send = fn (string $content, array $context) => postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $content]],
        'forwardedProps' => ['context' => $context],
    ])->assertOk()->streamedContent();

    $send('<context>'.Json::encode([$reference])."</context>\n\nRead this", entryContext($this->entry));
    $send('Thanks', [[...entryContext($this->entry)[0], 'ref' => 'editor-1', 'open' => true]]);

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Thanks'
        && str_contains($prompt->agent->instructions(), 'which the user has open in their editor')
        && ! str_contains($prompt->agent->instructions(), 'Saved summary'));
});

it('previews values that aren’t text as their fields, before and after, with only their own values', function () {
    $featured = Field::factory()->create(['name' => 'Featured', 'handle' => 'featured', 'type' => Lightswitch::class]);
    $notes = Field::factory()->create(['name' => 'Internal notes', 'handle' => 'internalNotes', 'type' => Lightswitch::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $featured->uid]),
        new CustomField(config: ['fieldUid' => $notes->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $entry->id, 'fields' => ['featured' => true]]),
        'Featured it.',
    ]);

    chatAboutEntry($entry)->assertOk()->streamedContent();

    $proposal = proposeChangesResult()['proposals'][0];

    expect($proposal['value'])->toBeTrue()
        ->and($proposal['diff'])->toBeNull()
        ->and($proposal['preview']['current']['nodes'])->toHaveCount(1)
        ->and($proposal['preview']['proposed']['nodes'])->toHaveCount(1)
        ->and($proposal['node']['control']['path'])->toBe(['fields', 'featured'])
        ->and(array_values(Arr::dot($proposal['preview']['proposed']['values'])))->toBe([true]);
});

it('only shows the model the fields the user added', function () {
    $notes = Field::factory()->create(['name' => 'Notes', 'handle' => 'notes', 'type' => PlainText::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => Fields::getFieldByHandle('summary')->uid]),
        new CustomField(config: ['fieldUid' => $notes->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    $entry->setFieldValue('notes', 'Saved notes');
    Elements::saveElement($entry);
    AssistantAgent::fake(['Done.']);

    chatAboutEntry($entry, ['fields.summary'])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), '"summary":"Unsaved summary"')
        && ! str_contains($prompt->agent->instructions(), 'Saved notes'));
});

it('turns away a proposed value its field can’t take, saying why', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $this->entry->id, 'fields' => ['summary' => ['not' => 'text']]]),
        'Done.',
    ]);

    chatAboutEntry($this->entry)->assertOk()->streamedContent();

    expect(proposeChangesResult()['proposals'])->toBe([])
        ->and(proposeChangesResult()['message'])->toContain('/summary');
});

it('says which attributes and fields aren’t in the editor, so it can’t propose them, while proposing the rest', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $this->entry->id, 'attributes' => ['slug' => 'new-slug'], 'fields' => ['summary' => 'New summary']]),
        'Done.',
    ]);

    chatAboutEntry($this->entry)->assertOk()->streamedContent();

    expect(array_column(proposeChangesResult()['proposals'], 'label'))->toBe(['Summary'])
        ->and(proposeChangesResult()['message'])->toContain('slug isn’t in the user’s editor');
});

it('lists the fields an element can add to the context, by the paths a message’s context takes', function () {
    postJson(action([AssistantController::class, 'contextFields']), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonPath('fields', [['value' => 'fields.summary', 'label' => 'Summary']]);
});

it('only lets the model propose changes to elements the user can save', function () {
    actingAs(UserModel::factory()->withPermissions([
        'accessCp',
        Assistant::PERMISSION,
        "viewEntries:{$this->section->uid}",
        "viewPeerEntries:{$this->section->uid}",
    ])->createElement());
    AssistantAgent::fake(['Done.']);

    chatAboutEntry($this->entry)->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'You can’t propose changes to it.')
        && ! in_array('propose_changes', array_map(static fn ($tool): string => $tool->name(), [...$prompt->agent->tools()]), true));
});

it('offers to add fields to the AI chat to users who can use it', function (bool $canUse) {
    actingAs(UserModel::factory()->withPermissions(array_filter(['accessCp', $canUse ? Assistant::PERMISSION : null]))->createElement());

    $fieldNode = $this->entry->getFieldLayout()->getCustomFieldElements()[0]->uiNode(new FieldLayoutElementContext($this->entry, new UiContext));
    $labels = collect($fieldNode->children())
        ->filter(fn ($node) => $node instanceof ActionMenu)
        ->flatMap(fn (ActionMenu $node) => $node->props()['items'])
        ->pluck('label');

    expect($labels->contains('Add to AI chat'))->toBe($canUse);
})->with(['with the permission' => true, 'without it' => false]);

it('describes the items of other context types to the model, and offers their tools while they’re in the context', function () {
    app(AssistantContextTypes::class)->register(TestOrderContext::class);
    AssistantAgent::fake(['Done.']);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Refund this']],
        'forwardedProps' => ['context' => [
            ['type' => 'test:order', 'ref' => 'ref-1', 'orderId' => 42],
            ['type' => 'unknown:thing', 'ref' => 'ref-2'],
        ]],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Order #42')
        && in_array('refund_order', array_map(static fn ($tool): string => $tool->name(), [...$prompt->agent->tools()]), true)
        && ! in_array('propose_changes', array_map(static fn ($tool): string => $tool->name(), [...$prompt->agent->tools()]), true));
});

it('leaves out elements that aren’t identified as the editor identifies them', function (array $item) {
    AssistantAgent::fake(['Hello.']);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Improve this']],
        'forwardedProps' => ['context' => [[...entryContext($this->entry)[0], ...$item]]],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => ! str_contains($prompt->agent->instructions(), 'e1:'));
})->with([
    'an element type that isn’t a string' => [['elementType' => []]],
    'a namespace that isn’t a list of strings' => [['namespace' => [['a']]]],
]);

it('fills the context’s places with the elements that resolve, past those that don’t', function () {
    AssistantAgent::fake(['Hello.']);
    $entries = collect(range(1, ElementContexts::MAX))->map(fn (int $i): Entry => EntryModel::factory()
        ->forSection($this->section)
        ->createElement(['title' => "Entry $i"]));

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Improve this']],
        'forwardedProps' => ['context' => [
            [...entryContext($this->entry)[0], 'elementId' => 999999],
            ...$entries->map(fn (Entry $entry): array => [...entryContext($entry)[0], 'values' => []])->all(),
        ]],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Entry '.ElementContexts::MAX));
});

it('resumes with the context the user’s answers to approval requests come with', function () {
    AssistantAgent::fake([
        new ToolCall('call_1', 'elements_delete', ['type' => 'entries', 'id' => 999]),
        'Done.',
    ]);
    $threadId = (string) Str::uuid();
    chatAboutEntry($this->entry, threadId: $threadId)->assertOk()->streamedContent();

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [],
        'resume' => [['interruptId' => 'call_1', 'status' => 'resolved', 'payload' => ['approved' => false]]],
        'forwardedProps' => ['context' => entryContext($this->entry)],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => $prompt->hasApprovalDecisions()
        && in_array('propose_changes', array_map(static fn ($tool): string => $tool->name(), [...$prompt->agent->tools()]), true)
        && str_contains($prompt->agent->instructions(), '"summary":"Unsaved summary"'));
});

/**
 * Creates an entry with a Matrix field of inline notes, each with a text field.
 */
function entryWithNotes(): Entry
{
    $text = Field::factory()->create(['name' => 'Text', 'handle' => 'text', 'type' => PlainText::class]);
    $noteType = EntryType::factory()->withField($text)->create(['handle' => 'note', 'hasTitleField' => false, 'titleFormat' => '{id}']);

    return EntryModel::factory()->withField(
        'notes', Matrix::class, ['entryTypes' => [$noteType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
        value: ['new1' => ['type' => 'note', 'fields' => ['text' => 'Old text']]],
    )->createElementWithFields()->element;
}

it('proposes a change to an entry nested in a Matrix field by its own ID, at its place in the editor’s form', function () {
    $owner = entryWithNotes();
    $note = $owner->getFieldValue('notes')->one();
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $note->id, 'fields' => ['text' => 'New text']]),
        'Done.',
    ]);

    chatAboutEntry($owner)->assertOk()->streamedContent();

    $proposal = proposeChangesResult()['proposals'][0];

    expect($proposal['path'])->toBe(['fields', 'notes', 'entries', array_key_first(Fields::getFieldByHandle('notes')->inlineEntries($owner)), 'fields', 'text'])
        ->and($proposal['diff'])->toBe(['current' => 'Old text', 'proposed' => 'New text'])
        ->and($proposal['elementId'])->toBe($owner->id)
        ->and($proposal['cpEditUrl'])->toBe($owner->getCpEditUrl())
        ->and(Entry::find()->id($note->id)->one()->getFieldValue('text'))->toBe('Old text');
});

it('keeps nothing that setting a Matrix field’s value on a draft writes', function () {
    $owner = entryWithNotes();
    $note = $owner->getFieldValue('notes')->one();
    $draft = app(Drafts::class)->createDraft($owner, User::findOne()->id, provisional: true);
    $entries = DB::table(Table::ENTRIES)->count();
    AssistantAgent::fake([
        new ToolCall('call_1', 'propose_changes', ['id' => $owner->id, 'fields' => ['notes' => [
            'entries' => [['note' => ['id' => $note->id, 'text' => 'New text']]],
        ]]]),
        'Done.',
    ]);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Improve this']],
        'forwardedProps' => ['context' => [[...entryContext($owner)[0], 'draftId' => $draft->draftId]]],
    ])->assertOk()->streamedContent();

    expect(proposeChangesResult()['proposals'])->not->toBe([])
        ->and(DB::table(Table::ENTRIES)->count())->toBe($entries);
});

it('shows where a field is in an element’s editor, by the path of its control', function (string $field, ?array $shown) {
    request()->setUserResolver(static fn () => auth()->user());

    $result = Json::decode(app(ShowField::class)->handle(new ToolRequest(['type' => 'entries', 'id' => $this->entry->id, 'field' => $field])));

    expect($result['field'] ?? null)->toBe($shown);
})->with([
    'custom field' => ['summary', ['label' => 'Summary', 'path' => ['fields', 'summary']]],
    'sidebar field' => ['slug', ['label' => 'Slug', 'path' => ['slug']]],
    'not in the editor' => ['body', null],
]);

it('only lets admins show settings', function (bool $admin) {
    if (! $admin) {
        actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION, "viewEntries:{$this->section->uid}"])->createElement());
    }
    AssistantAgent::fake(['Done.']);

    chatAboutEntry($this->entry)->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => in_array('show_components', array_map(static fn ($tool): string => $tool->name(), [...$prompt->agent->tools()]), true) === $admin);
})->with([
    'admin' => [true],
    'not an admin' => [false],
]);

it('shows settings as chips that link to where they’re edited, leaving out those that don’t exist', function () {
    $tool = app(ShowComponents::class);

    $result = Json::decode($tool->forDisplay($tool->handle(new ToolRequest(['components' => [
        ['type' => 'sections', 'id' => $this->section->id],
        ['type' => 'fields', 'id' => 999999],
    ]]))));

    expect($result['message'])->toContain('1 couldn’t be found')
        ->and($result['components'])->toHaveCount(1)
        ->and($result['components'][0])->toMatchArray(['type' => 'sections', 'id' => $this->section->id, 'cpEditUrl' => Url::cpUrl("settings/sections/{$this->section->id}")]);
});

it('shows an element nested in a Matrix field as the entry it’s in, and tells the model', function () {
    request()->setUserResolver(static fn () => auth()->user());
    $owner = entryWithNotes();
    $note = $owner->getFieldValue('notes')->one();

    $result = Json::decode(app(ShowElements::class)->handle(new ToolRequest(['elements' => [
        ['type' => 'entries', 'id' => $note->id],
        ['type' => 'entries', 'id' => $owner->id],
    ]])));

    expect(array_column($result['elements'], 'id'))->toBe([$owner->id])
        ->and($result['message'])->toContain("“{$owner->getUiLabel()}”");
});
