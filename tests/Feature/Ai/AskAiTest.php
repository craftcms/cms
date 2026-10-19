<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AskAi\Assistant;
use CraftCms\Cms\Ai\AskAi\AssistantAgent;
use CraftCms\Cms\Ai\Http\Controllers\AskAiController;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
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
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;

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

function askAi(Entry $entry, array $params = []): TestResponse
{
    return postJson(action([AskAiController::class, 'ask']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'prompt' => 'Improve this',
        ...$params,
    ]);
}

function summaryUid(Entry $entry): string
{
    return $entry->getFieldLayout()->getCustomFieldElements()[0]->uid;
}

it('proposes the changed fields without saving them', function () {
    AssistantAgent::fake([['values' => ['title' => 'Lighthouses', 'summary' => 'New summary'], 'summary' => null, 'findings' => []]]);

    askAi($this->entry)
        ->assertOk()
        ->assertJsonCount(1, 'proposals')
        ->assertJsonPath('proposals.0.layoutElementUid', summaryUid($this->entry))
        ->assertJsonPath('proposals.0.value', 'New summary');

    expect(Entry::find()->id($this->entry->id)->one()->getFieldValue('summary'))->toBe('Old summary');
});

it('only writes the target field of a field-level request', function () {
    AssistantAgent::fake([['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []]]);

    askAi($this->entry, ['layoutElementUid' => summaryUid($this->entry)])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => array_keys($prompt->agent->schema(new JsonSchemaTypeFactory)['values']->toArray()['properties']) === ['summary']);
});

it('resolves field references in the prompt', function () {
    AssistantAgent::fake([['values' => ['summary' => null], 'summary' => null, 'findings' => []]]);

    askAi($this->entry, ['prompt' => 'Shorten “{summary}” for {title}. Keep {unknown}.'])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, 'Shorten “Old summary” for Lighthouses. Keep {unknown}.'));
});

it('returns a review with findings when nothing was changed', function () {
    AssistantAgent::fake([[
        'values' => ['title' => null, 'summary' => null],
        'summary' => 'Reads well overall.',
        'findings' => [
            ['field' => 'summary', 'issue' => 'Repeats the title.'],
            ['field' => 'unknownField', 'issue' => 'Add a call to action.'],
        ],
    ]]);

    askAi($this->entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonCount(0, 'proposals')
        ->assertJsonPath('summary', 'Reads well overall.')
        ->assertJsonPath('findings.0', ['layoutElementUid' => summaryUid($this->entry), 'label' => 'Summary', 'quote' => null, 'issue' => 'Repeats the title.'])
        ->assertJsonPath('findings.1', ['layoutElementUid' => null, 'label' => null, 'quote' => null, 'issue' => 'Add a call to action.']);
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
    AssistantAgent::fake([[
        'values' => ['summary' => null],
        'summary' => null,
        'findings' => [['field' => 'related', 'issue' => 'Link the guide to Fresnel lenses.']],
    ]]);

    askAi($entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonPath('findings.0.label', 'Related')
        ->assertJsonPath('findings.0.layoutElementUid', $entry->getFieldLayout()->getCustomFieldElements()[1]->uid);
});

it('shows changed text as a diff', function (?string $current, string $currentText) {
    $this->entry->setFieldValue('summary', $current);
    Elements::saveElement($this->entry);
    AssistantAgent::fake([['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []]]);

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
    AssistantAgent::fake([['values' => ['featured' => true], 'summary' => null, 'findings' => []]]);

    $response = askAi($entry)
        ->assertOk()
        ->assertJsonPath('proposals.0.value', true)
        ->assertJsonPath('proposals.0.diff', null);

    expect($response->json('proposals.0.preview.current.nodes'))->toHaveCount(1)
        ->and($response->json('proposals.0.preview.proposed.nodes'))->toHaveCount(1);
});

it('works on the form’s unsaved values', function () {
    AssistantAgent::fake([['values' => ['summary' => 'New summary'], 'summary' => null, 'findings' => []]]);

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
    AssistantAgent::fake([['values' => ['summary' => null], 'summary' => null, 'findings' => []]]);

    askAi($entry, ['prompt' => 'Review this', 'includeElement' => true])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, '"prompt":"Stored prompt"'));
});

it('opens for a field that an unsaved change revealed', function () {
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
        'layoutElementUid' => $teaserUid,
        'fields' => ['featured' => true],
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Ask AI about “Teaser”');
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
    AssistantAgent::fake([['values' => ['summary' => null], 'summary' => null, 'findings' => []]]);

    askAi($entry, ['prompt' => 'Use “{secret}” and “{title}”.', 'includeElement' => true])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains($prompt->prompt, 'keeper')
        && ! str_contains($prompt->prompt, 'Lighthouses'));
});

it('lets option fields stay unchanged', function () {
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
    AssistantAgent::fake([['values' => ['color' => null], 'summary' => null, 'findings' => []]]);

    askAi($entry, ['layoutElementUid' => $entry->getFieldLayout()->getCustomFieldElements()[0]->uid])->assertOk();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => in_array(null, $prompt->agent->schema(new JsonSchemaTypeFactory)['values']->toArray()['properties']['color']['enum'], true));
});

it('proposes values for an inline nested entry’s fields', function () {
    $innerText = Field::factory()->create(['name' => 'Inner Text', 'handle' => 'innerText', 'type' => PlainText::class]);
    $blockType = EntryType::factory()->withField($innerText)->create(['handle' => 'block']);
    $matrix = Field::factory()->create([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
    ]);
    $layout = FieldLayoutModel::factory()->withContentTab([new CustomField(config: ['fieldUid' => $matrix->uid])]);
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
    /** @var Entry $block */
    $block = $owner->getFieldValue('blocks')->one();
    AssistantAgent::fake([['values' => ['innerText' => 'New text'], 'summary' => null, 'findings' => []]]);

    askAi($block, [
        'layoutElementUid' => $block->getFieldLayout()->getCustomFieldElements()[0]->uid,
        'namespace' => "fields.blocks.entries.$blockUid",
        'fields' => ['innerText' => 'Unsaved text'],
    ])
        ->assertOk()
        ->assertJsonPath('proposals.0.node.control.path', ['fields', 'blocks', 'entries', $blockUid, 'fields', 'innerText'])
        ->assertJsonPath('proposals.0.value', 'New text')
        ->assertJsonPath('proposals.0.diff.current', 'Unsaved text');
});

it('keeps only quotes that are in the field', function () {
    AssistantAgent::fake([[
        'values' => ['summary' => null],
        'summary' => null,
        'findings' => [
            ['field' => 'summary', 'quote' => 'Old', 'issue' => 'Vague.'],
            ['field' => 'summary', 'quote' => 'Not in the field', 'issue' => 'Made up.'],
        ],
    ]]);

    askAi($this->entry, ['prompt' => 'Review this'])
        ->assertOk()
        ->assertJsonPath('findings.0.quote', 'Old')
        ->assertJsonPath('findings.1.quote', null);
});

it('rejects generated values that do not match the field', function () {
    AssistantAgent::fake([['values' => ['summary' => ['not', 'text']], 'summary' => null, 'findings' => []]]);

    askAi($this->entry)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'The generated value for “Summary” isn’t valid.');
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
    AssistantAgent::fake([['values' => ['title' => null, 'summary' => null], 'summary' => null, 'findings' => []]]);

    askAi($this->entry)->assertStatus($status);
})->with([
    'without the Ask AI permission' => [false, true, 403],
    'without permission to save' => [true, false, 403],
    'with both' => [true, true, 200],
]);

it('offers the actions only while the task is available', function (bool $hasDefault) {
    app()->instance(Settings::class, new Settings($hasDefault ? ['defaults' => ['text' => ['provider' => 'anthropic']]] : []));

    $elementLabels = collect($this->entry->actionMenuDescriptors(ElementActionContext::Editor))->pluck('label');
    $fieldNode = $this->entry->getFieldLayout()->getCustomFieldElements()[0]->uiNode(new FieldLayoutElementContext($this->entry, new UiContext));
    $fieldLabels = collect($fieldNode->children())
        ->filter(fn ($node) => $node instanceof ActionMenu)
        ->flatMap(fn (ActionMenu $node) => $node->props()['items'])
        ->pluck('label');

    expect($elementLabels->contains('Ask AI'))->toBe($hasDefault)
        ->and($fieldLabels->contains('Ask AI'))->toBe($hasDefault);
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
    AssistantAgent::fake([['values' => ['alt' => 'A lighthouse at dusk'], 'summary' => null, 'findings' => []]]);

    postJson(action([AskAiController::class, 'ask']), [
        'elementType' => Asset::class,
        'elementId' => $asset->id,
        'siteId' => $asset->siteId,
        'prompt' => 'Describe the image',
        'includeImage' => $includeImage,
    ])->assertOk()->assertJsonPath('proposals.0.value', 'A lighthouse at dusk');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->attachments->count() === $attachments);
})->with([
    'included' => [true, 1],
    'left out' => [false, 0],
]);
