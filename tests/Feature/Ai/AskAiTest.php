<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AskAi\Assistant;
use CraftCms\Cms\Ai\AskAi\AssistantAgent;
use CraftCms\Cms\Ai\AskAi\ProposeChanges;
use CraftCms\Cms\Ai\Http\Controllers\AskAiController;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutDefinition;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\TextResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    actingAs(User::findOne());

    $this->field = Field::factory()->create([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ]);
    $this->entryType = EntryType::factory()->withField($this->field)->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $this->entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Lighthouses']);
    $this->entry->setFieldValue('summary', 'Old summary');
    Elements::saveElement($this->entry);
});

/**
 * Asks, and returns the streamed `result` event as a JSON response, or the `error` event as an error response with its
 * status.
 */
function askAi(Entry $entry, array $params = []): TestResponse
{
    $response = postJson(action([AskAiController::class, 'ask']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'prompt' => 'Improve this',
        ...$params,
    ]);

    if (! $response->baseResponse instanceof StreamedResponse) {
        return $response;
    }

    $events = array_map(
        static fn (string $line): array => json_decode($line, true),
        array_filter(explode("\n", $response->streamedContent())),
    );
    $end = array_find($events, static fn (array $event): bool => in_array($event['type'], ['result', 'error'], true));

    if ($end['type'] === 'error') {
        return TestResponse::fromBaseResponse(new JsonResponse(['message' => $end['message']], $end['status']));
    }

    return TestResponse::fromBaseResponse(new JsonResponse($end['result']));
}

/**
 * Returns a fake response from the model: its summary as text, then its answer as a `propose_changes` call.
 *
 * @param  array{values?: array<string, mixed>, summary?: string|null, findings?: list<array<string, mixed>>}  $answer
 */
function answer(array $answer): TextResponse|ToolCall
{
    $arguments = [
        'findings' => array_map(static fn (array $finding): array => ['quote' => null, ...$finding], $answer['findings'] ?? []),
        'values' => $answer['values'] ?? [],
    ];

    if (($answer['summary'] ?? null) === null) {
        return new ToolCall('call_1', ProposeChanges::NAME, $arguments);
    }

    // A fake step can only have text and a tool call together as a call awaiting approval.
    return new TextResponse($answer['summary'], new TextUsage, new Meta('fake', 'fake'))
        ->withPendingApprovals(new Collection([new PendingApproval('call_1', ProposeChanges::NAME, $arguments)]));
}

/**
 * Returns the schema of the `propose_changes` tool the agent was prompted with.
 *
 * @return array<string, Type>
 */
function toolSchema(AgentPrompt $prompt): array
{
    return [...$prompt->agent->tools()][0]->schema(new JsonSchemaTypeFactory);
}

it('proposes the changed fields without saving them', function () {
    AssistantAgent::fake([answer(['values' => ['title' => 'Lighthouses', 'summary' => 'New summary'], 'summary' => null, 'findings' => []])]);

    askAi($this->entry)
        ->assertOk()
        ->assertJsonCount(1, 'proposals')
        ->assertJsonPath('proposals.0.key', 'summary')
        ->assertJsonPath('proposals.0.value', 'New summary');

    expect(Entry::find()->id($this->entry->id)->one()->getFieldValue('summary'))->toBe('Old summary');
});

it('streams the summary as it’s written, before the result', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => 'New summary'], 'summary' => 'Shortened the summary.'])]);

    $response = postJson(action([AskAiController::class, 'ask']), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'prompt' => 'Shorten the summary',
    ])->assertOk();

    $events = array_map(
        static fn (string $line): array => json_decode($line, true),
        array_values(array_filter(explode("\n", $response->streamedContent()))),
    );

    expect(array_unique(array_column(array_slice($events, 0, -1), 'type')))->toBe(['summary'])
        ->and(implode('', array_column(array_slice($events, 0, -1), 'delta')))->toBe('Shortened the summary.')
        ->and(end($events)['type'])->toBe('result')
        ->and(end($events)['result']['summary'])->toBe('Shortened the summary.');
});

it('keeps an answer without a proposal as a review', function () {
    AssistantAgent::fake(['Reads well overall.']);

    askAi($this->entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonPath('summary', 'Reads well overall.')
        ->assertJsonCount(0, 'proposals')
        ->assertJsonCount(0, 'findings');
});

it('only sends and writes the fields chosen as context', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []])]);

    askAi($this->entry, ['context' => ['fields.summary']])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => array_keys(toolSchema($prompt)['values']->toArray()['properties']) === ['summary']
        && ! str_contains($prompt->prompt, 'Lighthouses'));
});

it('resolves field references in the prompt', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => null], 'summary' => null, 'findings' => []])]);

    askAi($this->entry, ['prompt' => 'Shorten “{summary}” for {title}. Keep {unknown}.'])
        ->assertOk()
        ->assertJsonPath('turn.prompt', 'Shorten “Old summary” for Lighthouses. Keep {unknown}.');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, 'Shorten “Old summary” for Lighthouses. Keep {unknown}.'));
});

it('returns a review with findings when nothing was changed', function () {
    AssistantAgent::fake([answer([
        'values' => ['title' => null, 'summary' => null],
        'summary' => 'Reads well overall.',
        'findings' => [
            ['field' => 'summary', 'issue' => 'Repeats the title.'],
            ['field' => 'unknownField', 'issue' => 'Add a call to action.'],
        ],
    ])]);

    askAi($this->entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonCount(0, 'proposals')
        ->assertJsonPath('summary', 'Reads well overall.')
        ->assertJsonPath('findings.0', ['key' => 'summary', 'path' => 'fields.summary', 'label' => 'Summary', 'quote' => null, 'issue' => 'Repeats the title.'])
        ->assertJsonPath('findings.1', ['key' => null, 'path' => null, 'label' => null, 'quote' => null, 'issue' => 'Add a call to action.']);
});

it('follows up on the earlier turns of the conversation, sent as they were', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => 'Shorter summary'], 'summary' => 'Shortened it more.', 'findings' => []])]);
    $earlier = ['prompt' => 'Shorten “{summary}”', 'answer' => '{"findings":[],"summary":"Shortened it.","values":{"summary":"Short summary"}}'];

    askAi($this->entry, ['prompt' => 'Even shorter', 'history' => [$earlier], 'fields' => ['summary' => 'Short summary']])
        ->assertOk()
        ->assertJsonPath('turn.answer', '{"findings":[],"summary":"Shortened it more.","values":{"summary":"Shorter summary"}}');

    // Replayed as the model gave it: its summary, then its call of the tool, with the tool’s result.
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => array_map(
        static fn (Message $message): array => [
            $message->role->value,
            $message->content,
            ...match (true) {
                $message instanceof AssistantMessage => $message->toolCalls->map(static fn (ToolCall $call): array => [$call->name, $call->arguments])->all(),
                $message instanceof ToolResultMessage => $message->toolResults->map(static fn (ToolResult $result): string => $result->result)->all(),
                default => [],
            },
        ],
        [...$prompt->agent->messages()],
    ) === [
        ['user', "Instructions:\nShorten “{summary}”"],
        ['assistant', 'Shortened it.', [ProposeChanges::NAME, ['findings' => [], 'values' => ['summary' => 'Short summary']]]],
        ['tool_result', null, ProposeChanges::RESULT],
    ]);

    // The call and its result share an id of nine letters and digits, the only format Mistral accepts.
    AssistantAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        [, $call, $result] = [...$prompt->agent->messages()];
        $id = $call->toolCalls->first()->resultId;

        return preg_match('/^[a-zA-Z0-9]{9}$/', $id) === 1 && $result->toolResults->first()->resultId === $id;
    });
});

it('accepts earlier turns as they were returned, however long, or empty, their prompts', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => null], 'summary' => null, 'findings' => []])]);
    $answer = '{"findings":[],"summary":"Done.","values":[]}';

    askAi($this->entry, ['history' => [
        ['prompt' => str_repeat('A long field value. ', 1000), 'answer' => $answer],
        ['prompt' => '', 'answer' => $answer],
    ]])->assertOk();
});

it('maps findings to fields that cannot be written', function () {
    $related = Field::factory()->create(['name' => 'Related', 'handle' => 'related', 'type' => Entries::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $this->field->uid]),
        new CustomField(config: ['fieldUid' => $related->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([answer([
        'values' => ['summary' => null],
        'summary' => null,
        'findings' => [['field' => 'related', 'issue' => 'Link the guide to Fresnel lenses.']],
    ])]);

    askAi($entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonPath('findings.0.label', 'Related')
        ->assertJsonPath('findings.0.key', 'related');
});

it('shows changed text as a diff', function (?string $current, string $currentText) {
    $this->entry->setFieldValue('summary', $current);
    Elements::saveElement($this->entry);
    AssistantAgent::fake([answer(['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []])]);

    askAi($this->entry)
        ->assertOk()
        ->assertJsonPath('proposals.0.diff', ['current' => $currentText, 'proposed' => 'New summary'])
        ->assertJsonPath('proposals.0.preview', null);
})->with([
    'existing text' => ['Old summary', 'Old summary'],
    'an empty field' => [null, ''],
]);

it('previews values that are not text', function () {
    $featured = Field::factory()->create(['name' => 'Featured', 'handle' => 'featured', 'type' => Lightswitch::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([new CustomField(config: ['fieldUid' => $featured->uid])]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([answer(['values' => ['featured' => true], 'summary' => null, 'findings' => []])]);

    $response = askAi($entry)
        ->assertOk()
        ->assertJsonPath('proposals.0.value', true)
        ->assertJsonPath('proposals.0.diff', null);

    expect($response->json('proposals.0.preview.current.nodes'))->toHaveCount(1)
        ->and($response->json('proposals.0.preview.proposed.nodes'))->toHaveCount(1);
});

it('accepts dates and times in any format the fields can parse', function () {
    $opens = Field::factory()->create(['name' => 'Opens at', 'handle' => 'opens', 'type' => Time::class]);
    $eventDate = Field::factory()->create(['name' => 'Event date', 'handle' => 'eventDate', 'type' => Date::class, 'settings' => ['showTime' => true]]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $opens->uid]),
        new CustomField(config: ['fieldUid' => $eventDate->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    $entry->setFieldValues(['opens' => '09:30', 'eventDate' => new DateTime('2026-11-22T10:00:00+00:00')]);
    Elements::saveElement($entry);
    AssistantAgent::fake([answer(['values' => ['opens' => '2:30 PM', 'eventDate' => '2026-11-23T11:00:00+00:00'], 'summary' => null, 'findings' => []])]);

    askAi($entry)
        ->assertOk()
        ->assertJsonCount(2, 'proposals');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, '"opens":"09:30:00"')
        && preg_match('/"eventDate":"2026-11-2[12]T\d{2}:00:00[+-]\d{2}:\d{2}"/', $prompt->prompt) === 1);
});

it('leaves out a value its field can’t parse, rather than clearing the field, and keeps the rest', function () {
    $opens = Field::factory()->create(['name' => 'Opens at', 'handle' => 'opens', 'type' => Time::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $opens->uid]),
        new CustomField(config: ['fieldUid' => $this->field->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([answer(['values' => ['opens' => 'half past nine', 'summary' => 'New summary'], 'summary' => null, 'findings' => []])]);

    askAi($entry)
        ->assertOk()
        ->assertJsonCount(1, 'proposals')
        ->assertJsonPath('proposals.0.key', 'summary')
        ->assertJsonPath('findings.0.key', 'opens')
        ->assertJsonPath('turn.answer', '{"findings":[{"field":"opens","quote":null,"issue":"The generated value isn’t valid for this field, so it was left out."}],"summary":null,"values":{"summary":"New summary"}}');
});

it('works on the form’s unsaved values', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []])]);

    askAi($this->entry, ['fields' => ['summary' => 'Unsaved summary']])
        ->assertOk()
        ->assertJsonPath('proposals.0.diff.current', 'Unsaved summary');
});

it('keeps its own params out of same-named fields', function () {
    $promptField = Field::factory()->create(['name' => 'Prompt', 'handle' => 'prompt', 'type' => PlainText::class]);
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $this->field->uid]),
        new CustomField(config: ['fieldUid' => $promptField->uid]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    $entry->setFieldValue('prompt', 'Stored prompt');
    Elements::saveElement($entry);
    AssistantAgent::fake([answer(['values' => ['summary' => null], 'summary' => null, 'findings' => []])]);

    askAi($entry, ['prompt' => 'Review this'])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, '"prompt":"Stored prompt"'));
});

it('offers a field that an unsaved change revealed as context', function () {
    $featured = Field::factory()->create(['name' => 'Featured', 'handle' => 'featured', 'type' => Lightswitch::class]);
    $teaser = Field::factory()->create(['name' => 'Teaser', 'handle' => 'teaser', 'type' => PlainText::class]);
    $featuredUid = Str::uuid()->toString();
    $teaserUid = Str::uuid()->toString();
    Fields::refreshFields();
    $layout = FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['uid' => $featuredUid, 'fieldUid' => $featured->uid]),
        new CustomField(config: [
            'uid' => $teaserUid,
            'fieldUid' => $teaser->uid,
            'elementCondition' => [
                'class' => ElementCondition::class,
                'elementType' => Entry::class,
                'conditionRules' => [[
                    'class' => LightswitchFieldConditionRule::class,
                    'uid' => Str::uuid()->toString(),
                    'fieldUid' => $featured->uid,
                    'layoutElementUid' => $featuredUid,
                    'value' => true,
                ]],
            ],
        ]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);

    postJson(action([AskAiController::class, 'form']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'fields' => ['featured' => true],
    ])
        ->assertOk()
        ->assertJsonFragment(['value' => 'fields.teaser', 'label' => 'Teaser']);
});

it('leaves fields the user can’t see out of the prompt', function () {
    $secret = Field::factory()->create(['name' => 'Secret', 'handle' => 'secret', 'type' => PlainText::class]);
    $adminsOnly = [
        'class' => UserCondition::class,
        'conditionRules' => [['class' => AdminConditionRule::class, 'value' => true]],
    ];
    $layout = FieldLayoutModel::factory()->withContentTab([
        new EntryTitleField(config: ['userCondition' => $adminsOnly]),
        new CustomField(config: ['fieldUid' => $this->field->uid]),
        new CustomField(config: [
            'fieldUid' => $secret->uid,
            'userCondition' => $adminsOnly,
        ]),
    ]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    $entry->setFieldValue('secret', 'The keeper’s key is under the mat');
    Elements::saveElement($entry);
    actingAs(UserModel::factory()
        ->withPermissions(['accessCp', "viewEntries:{$section->uid}", "saveEntries:{$section->uid}", "viewPeerEntries:{$section->uid}", "savePeerEntries:{$section->uid}", Assistant::PERMISSION])
        ->createElement());
    AssistantAgent::fake([answer(['values' => ['summary' => null], 'summary' => null, 'findings' => []])]);

    askAi($entry, ['prompt' => 'Use “{secret}” and “{title}”.'])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains($prompt->prompt, 'keeper')
        && ! str_contains($prompt->prompt, 'Lighthouses'));
});

it('lets option fields and findings be null without a null enum value', function () {
    $color = Field::factory()->create([
        'name' => 'Color',
        'handle' => 'color',
        'type' => Dropdown::class,
        'settings' => ['options' => [['label' => 'Red', 'value' => 'red'], ['label' => 'Blue', 'value' => 'blue']]],
    ]);
    $layout = FieldLayoutModel::factory()->withContentTab([new CustomField(config: ['fieldUid' => $color->uid])]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([answer(['values' => ['color' => null], 'summary' => null, 'findings' => []])]);

    askAi($entry, ['context' => ['fields.color']])->assertOk();

    // Providers such as Anthropic reject `null` among an enum’s values, so it’s allowed by the type instead.
    AssistantAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        $schema = toolSchema($prompt);
        $color = $schema['values']->toArray()['properties']['color'];
        $findingField = $schema['findings']->toArray()['items']['properties']['field'];

        return in_array('null', $color['type'], true) && ! in_array(null, $color['enum'], true)
            && in_array('null', $findingField['type'], true) && ! in_array(null, $findingField['enum'], true);
    });
});

/**
 * Creates an entry with a Matrix field that shows one block inline.
 *
 * @return array{Entry, string} The owner, and its block’s UID.
 */
function ownerWithBlock(): array
{
    $innerText = Field::factory()->create(['name' => 'Inner Text', 'handle' => 'innerText', 'type' => PlainText::class]);
    $blockType = EntryType::factory()->withField($innerText)->create(['handle' => 'block']);
    $matrix = Field::factory()->create([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
    ]);
    $layout = FieldLayoutModel::factory()->withContentTab([new EntryTitleField, new CustomField(config: ['fieldUid' => $matrix->uid])]);
    $entryType = EntryType::factory()->withFieldLayout($layout)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    $owner = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Lighthouses']);
    $blockUid = Str::uuid()->toString();
    $owner->setFieldValueFromRequest('blocks', [
        'entries' => ["uid:$blockUid" => ['type' => 'block', 'title' => 'Block', 'fields' => ['innerText' => 'Stored text']]],
        'sortOrder' => [$blockUid],
    ]);
    Elements::saveElement($owner);

    return [$owner, $blockUid];
}

it('offers an inline nested entry’s fields as context', function () {
    [$owner, $blockUid] = ownerWithBlock();

    postJson(action([AskAiController::class, 'form']), [
        'elementType' => Entry::class,
        'elementId' => $owner->id,
        'siteId' => $owner->siteId,
    ])
        ->assertOk()
        ->assertJsonFragment(['value' => "fields.blocks.entries.$blockUid.fields.innerText", 'label' => 'Blocks › 1. Block › Inner Text']);
});

it('proposes values for an inline nested entry’s fields in the same request as the owner’s', function () {
    [$owner, $blockUid] = ownerWithBlock();
    AssistantAgent::fake([answer(['values' => ['title' => null, 'blocks.1.innerText' => 'New text'], 'summary' => null, 'findings' => []])]);

    askAi($owner, [
        'context' => ['title', "fields.blocks.entries.$blockUid.fields.innerText"],
        'fields' => ['blocks' => ['entries' => [$blockUid => ['fields' => ['innerText' => 'Unsaved text']]]]],
    ])
        ->assertOk()
        ->assertJsonPath('proposals.0.key', 'blocks.1.innerText')
        ->assertJsonPath('proposals.0.node.control.path', ['fields', 'blocks', 'entries', $blockUid, 'fields', 'innerText'])
        ->assertJsonPath('proposals.0.value', 'New text')
        ->assertJsonPath('proposals.0.diff.current', 'Unsaved text');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => array_keys(toolSchema($prompt)['values']->toArray()['properties']) === ['title', 'blocks.1.innerText']
        && str_contains($prompt->prompt, '"blocks.1" is item 1 of the “Blocks” field'));
});

it('keeps only quotes that are in the field', function () {
    AssistantAgent::fake([answer([
        'values' => ['summary' => null],
        'summary' => null,
        'findings' => [
            ['field' => 'summary', 'quote' => 'Old', 'issue' => 'Vague.'],
            ['field' => 'summary', 'quote' => 'Not in the field', 'issue' => 'Made up.'],
        ],
    ])]);

    askAi($this->entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonPath('findings.0.quote', 'Old')
        ->assertJsonPath('findings.1.quote', null);
});

it('leaves out a generated value that does not match its field, with a finding saying so', function () {
    AssistantAgent::fake([answer(['values' => ['summary' => ['not', 'text']], 'summary' => null, 'findings' => []])]);

    askAi($this->entry)
        ->assertOk()
        ->assertJsonCount(0, 'proposals')
        ->assertJsonPath('findings.0.key', 'summary')
        ->assertJsonPath('findings.0.issue', 'The generated value isn’t valid for this field, so it was left out.');
});

it('requires the Ask AI permission and permission to save the element', function (bool $canAsk, bool $canSave, int $status) {
    $user = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "viewEntries:{$this->section->uid}",
            "viewPeerEntries:{$this->section->uid}",
            ...($canSave ? ["saveEntries:{$this->section->uid}", "savePeerEntries:{$this->section->uid}"] : []),
            ...($canAsk ? [Assistant::PERMISSION] : []),
        ])
        ->createElement();
    actingAs($user);
    AssistantAgent::fake([answer(['values' => ['title' => null, 'summary' => null], 'summary' => null, 'findings' => []])]);

    askAi($this->entry)->assertStatus($status);
})->with([
    'without the Ask AI permission' => [false, true, 403],
    'without permission to save' => [true, false, 403],
    'with both' => [true, true, 200],
]);

it('offers the field action only while the task is available', function (bool $hasDefault) {
    app()->instance(Settings::class, new Settings($hasDefault ? ['defaults' => ['text' => ['provider' => 'anthropic']]] : []));

    $fieldNode = $this->entry->getFieldLayout()->getCustomFieldElements()[0]->uiNode(new FieldLayoutElementContext($this->entry, new UiContext));
    $fieldLabels = collect($fieldNode->children())
        ->filter(fn ($node) => $node instanceof ActionMenu)
        ->flatMap(fn (ActionMenu $node) => $node->props()['items'])
        ->pluck('label');

    expect($fieldLabels->contains('Ask AI'))->toBe($hasDefault);
})->with(['with a default provider' => true, 'without a default provider' => false]);

it('attaches the image of an image asset when asked to', function (bool $includeImage, int $attachments) {
    config()->set('filesystems.disks.ask-ai-test', ['driver' => 'local', 'root' => storage_path('framework/testing/ask-ai-test')]);
    Storage::fake('ask-ai-test');
    $layout = FieldLayoutDefinition::make(Asset::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new AltField));
    $volume = Volume::factory()->create([
        'fs' => 'ask-ai-test',
        'fieldLayoutId' => FieldLayoutModel::factory()->create(['type' => Asset::class, 'config' => $layout->getConfig()])->id,
    ]);
    $image = UploadedFile::fake()->image('lighthouse.png', 1600, 1200);
    Storage::disk('ask-ai-test')->put('lighthouse.png', file_get_contents($image->getPathname()));
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => app(Folders::class)->getRootFolderByVolumeId($volume->id)->id,
        'filename' => 'lighthouse.png',
        'kind' => FileKind::Image->value,
    ]);
    AssistantAgent::fake([answer(['values' => ['alt' => 'A lighthouse at dusk'], 'summary' => null, 'findings' => []])]);

    postJson(action([AskAiController::class, 'ask']), [
        'elementType' => Asset::class,
        'elementId' => $asset->id,
        'siteId' => $asset->siteId,
        'prompt' => 'Describe the image',
        'includeImage' => $includeImage,
    ])->assertOk()->assertJsonPath('result.proposals.0.value', 'A lighthouse at dusk');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->attachments->count() === $attachments);
})->with([
    'included' => [true, 1],
    'left out' => [false, 0],
]);
