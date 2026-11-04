<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\CompareFieldChangesController;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\Fields\HtmlText;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\Nodes\Field as FieldNode;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

beforeEach(function () {
    actingAs(User::findOne());

    $this->summaryField = Field::factory()->create([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ]);
    $this->bodyField = Field::factory()->create([
        'name' => 'Body',
        'handle' => 'body',
        'type' => HtmlText::class,
    ]);
    $this->colorField = Field::factory()->create([
        'name' => 'Color',
        'handle' => 'color',
        'type' => Dropdown::class,
        'settings' => ['options' => [['label' => 'Red', 'value' => 'red'], ['label' => 'Blue', 'value' => 'blue']]],
    ]);
    $this->headingField = Field::factory()->create([
        'name' => 'Heading',
        'handle' => 'heading',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $this->blockType = EntryType::factory()
        ->withField($this->headingField)
        ->create(['name' => 'Block', 'handle' => 'block', 'hasTitleField' => true]);
    $this->blocksField = Field::factory()->create([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$this->blockType->id]],
    ]);
    Fields::refreshFields();

    $this->entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $this->summaryField->uid]),
            new CustomField(config: ['fieldUid' => $this->bodyField->uid]),
            new CustomField(config: ['fieldUid' => $this->colorField->uid]),
            new CustomField(config: ['fieldUid' => $this->blocksField->uid]),
        ]))
        ->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();

    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create([
            'handle' => 'articles',
            'type' => SectionType::Channel,
            'enableVersioning' => true,
        ]);
});

function comparisonEntry(string $summary = 'Old summary'): Entry
{
    $entry = EntryModel::factory()
        ->forSection(test()->section)
        ->forEntryType(test()->entryType)
        ->createElement(['title' => 'Article']);
    $entry->setFieldValue('summary', $summary);
    expect(Elements::saveElement($entry))->toBeTrue();

    return $entry;
}

function comparisonFieldUid(Entry $entry, string $handle): string
{
    $layoutElement = collect($entry->getFieldLayout()->getCustomFieldElements())
        ->first(fn (CustomField $element) => $element->getField()->handle === $handle);

    expect($layoutElement?->uid)->not->toBeNull();

    return $layoutElement->uid;
}

/** @return list<string> */
function comparisonActionLabels(Entry $entry, string $handle): array
{
    $layoutElement = $entry->getFieldLayout()->getElementByUid(comparisonFieldUid($entry, $handle));
    $fieldNode = $layoutElement->uiNode(new FieldLayoutElementContext($entry, new UiContext));

    return collect($fieldNode->children())
        ->filter(fn ($node) => $node instanceof ActionMenu)
        ->flatMap(fn (ActionMenu $node) => $node->props()['items'])
        ->pluck('label')
        ->filter()
        ->values()
        ->all();
}

function draftWithSummary(Entry $entry, string $summary, bool $provisional = false): Entry
{
    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: $provisional);
    $draft->setFieldValue('summary', $summary);
    expect(Elements::saveElement($draft))->toBeTrue();

    return Entry::find()->id($draft->id)->drafts(null)->provisionalDrafts(null)->status(null)->one();
}

/** @return array{Entry, Entry} The draft, and its edited nested entry */
function draftWithEditedBlock(): array
{
    $entry = comparisonEntry();
    $entry->setFieldValue('blocks', [
        'new1' => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'Old heading']],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();

    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id());
    $block = $draft->getFieldValue('blocks')->status(null)->one();
    $draft->setFieldValue('blocks', [
        'sortOrder' => [$block->id],
        'entries' => [$block->id => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'New heading']]],
    ]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $draftBlock = Entry::find()
        ->ownerId($draft->id)
        ->fieldId(test()->blocksField->id)
        ->drafts(null)
        ->status(null)
        ->one();
    expect($draftBlock->getIsCanonical())->toBeFalse();

    return [$draft, $draftBlock];
}

function latestRevision(Entry $entry): Entry
{
    $id = app(Revisions::class)->createRevision($entry, auth()->id());

    return Entry::find()->id($id)->revisions(null)->status(null)->one();
}

it('requires authentication', function () {
    Auth::logout();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns 400 for canonical elements', function () {
    $entry = comparisonEntry();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'layoutElementUid' => comparisonFieldUid($entry, 'summary'),
    ])->assertBadRequest();
});

it('validates the request payload', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'status' => 'nope',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['layoutElementUid', 'status']);
});

it('returns 400 for invalid layout element uuids', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => Str::uuid()->toString(),
    ])->assertBadRequest();
});

it('forbids comparing drafts the user cannot view', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    actingAs(UserModel::factory()
        ->withPermissions([
            'accessCp',
            sprintf('editSite:%s', Sites::getPrimarySite()->uid),
            sprintf('viewEntries:%s', $this->section->uid),
            sprintf('viewPeerEntries:%s', $this->section->uid),
        ])
        ->createElement());

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertForbidden();
});

it('compares a draft with the current entry', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJson([
            'fromLabel' => t('Current'),
            'toLabel' => $draft->draftName,
            'hasBase' => true,
            'unchanged' => false,
            'diff' => ['current' => 'Old summary', 'proposed' => 'New summary'],
            'preview' => null,
        ]);
});

it('compares a draft that changed its entry type, by the field’s layout element on each side', function () {
    $otherType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $this->summaryField->uid]),
            new CustomField(config: ['fieldUid' => $this->headingField->uid]),
        ]))
        ->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();
    $this->section->entryTypes()->attach($otherType, ['sortOrder' => 2]);
    Sections::refreshSections();

    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft(comparisonEntry(), auth()->id());
    $draft->setTypeId($otherType->id);
    $draft->setFieldValue('summary', 'New summary');
    $draft->setFieldValue('heading', 'Only on the other type');
    expect(Elements::saveElement($draft))->toBeTrue();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('diff', ['current' => 'Old summary', 'proposed' => 'New summary']);
});

it('calls a provisional draft the user’s changes', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary', provisional: true);

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('toLabel', t('Your changes'))
        ->assertJsonPath('diff.proposed', 'New summary');
});

it('reports values that are the same on both sides', function () {
    $draft = draftWithSummary(comparisonEntry(), 'Old summary');

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('unchanged', true);
});

it('compares a revision with the revision before it', function () {
    $entry = comparisonEntry('First summary');
    $first = latestRevision($entry);

    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Second summary');
    expect(Elements::saveElement($entry))->toBeTrue();
    $second = latestRevision($entry);

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $second->id,
        'siteId' => $second->siteId,
        'layoutElementUid' => comparisonFieldUid($second, 'summary'),
    ])->assertOk()
        ->assertJson([
            'fromLabel' => $first->getRevisionLabel(),
            'toLabel' => $second->getRevisionLabel(),
            'hasBase' => true,
            'unchanged' => false,
            'diff' => ['current' => 'First summary', 'proposed' => 'Second summary'],
            'preview' => null,
        ]);
});

it('has nothing to compare an entry’s first revision with', function () {
    $revision = Entry::find()
        ->revisionOf(comparisonEntry())
        ->status(null)
        ->orderBy('revisions.num')
        ->one();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revision->id,
        'siteId' => $revision->siteId,
        'layoutElementUid' => comparisonFieldUid($revision, 'summary'),
    ])->assertOk()
        ->assertJsonPath('fromLabel', t('No earlier revision'))
        ->assertJsonPath('hasBase', false)
        ->assertJsonPath('preview.current', null)
        ->assertJsonCount(1, 'preview.proposed.nodes');
});

it('compares an outdated field’s change on the current entry', function () {
    $entry = comparisonEntry('Old summary');
    $before = latestRevision($entry);

    travel(1)->minutes();
    $draft = app(Drafts::class)->createDraft($entry, auth()->id());

    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Updated summary');
    expect(Elements::saveElement($entry))->toBeTrue();
    latestRevision($entry);

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
        'status' => 'outdated',
    ])->assertOk()
        ->assertJson([
            'fromLabel' => $before->getRevisionLabel(),
            'toLabel' => t('Current'),
            'hasBase' => true,
            'unchanged' => false,
            'diff' => ['current' => 'Old summary', 'proposed' => 'Updated summary'],
            'preview' => null,
        ]);
});

it('compares a field in a nested entry with the current entry’s', function () {
    [$draft, $draftBlock] = draftWithEditedBlock();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draftBlock->id,
        'ownerId' => $draft->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $draftBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($draftBlock, 'heading'),
    ])->assertOk()
        ->assertJson([
            'fromLabel' => t('Current'),
            'toLabel' => $draft->draftName,
            'hasBase' => true,
            'unchanged' => false,
            'diff' => ['current' => 'Old heading', 'proposed' => 'New heading'],
            'preview' => null,
        ]);
});

it('previews fields that aren’t text, such as Matrix fields, read-only on both sides', function () {
    // Inline blocks nest their entries’ fields in the preview.
    $this->blocksField->update(['settings' => [...$this->blocksField->settings, 'viewMode' => Matrix::VIEW_MODE_BLOCKS]]);
    Fields::refreshFields();
    [$draft] = draftWithEditedBlock();

    $response = postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'blocks'),
    ])->assertOk()
        ->assertJsonPath('diff', null)
        ->assertJsonPath('unchanged', false)
        ->assertJsonCount(1, 'preview.current.nodes')
        ->assertJsonCount(1, 'preview.proposed.nodes');

    // There’s nothing to act on in a preview, so neither the field nor its entries’ fields have action menus.
    $types = fn (string $side): array => array_values(array_filter(
        Arr::flatten($response->json("preview.$side.nodes")),
        fn ($value) => is_string($value) && str_starts_with($value, 'CraftCms\\Cms\\Ui\\Nodes\\'),
    ));

    expect($response->json('preview.current.nodes.0.control.mode'))->toBe('readOnly')
        ->and($response->json('preview.proposed.nodes.0.control.uis.0.nodes'))->not->toBeEmpty()
        ->and($types('current'))->toContain(FieldNode::class)->not->toContain(ActionMenu::class)
        ->and($types('proposed'))->toContain(FieldNode::class)->not->toContain(ActionMenu::class);
});

it('compares a field in a nested entry with the owner’s previous revision', function () {
    $entry = comparisonEntry();
    travel(1)->minutes();
    $entry->setFieldValue('blocks', [
        'new1' => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'Old heading']],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();
    $first = latestRevision($entry);

    travel(1)->minutes();
    $block = $entry->getFieldValue('blocks')->status(null)->one();
    $entry->setFieldValue('blocks', [
        'sortOrder' => [$block->id],
        'entries' => [$block->id => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'New heading']]],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();
    $second = latestRevision($entry);

    $revisionBlock = Entry::find()
        ->ownerId($second->id)
        ->fieldId($this->blocksField->id)
        ->revisions(null)
        ->status(null)
        ->one();
    expect($revisionBlock->getIsRevision())->toBeTrue();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revisionBlock->id,
        'ownerId' => $second->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $revisionBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($revisionBlock, 'heading'),
    ])->assertOk()
        ->assertJson([
            'fromLabel' => $first->getRevisionLabel(),
            'toLabel' => $second->getRevisionLabel(),
            'hasBase' => true,
            'unchanged' => false,
            'diff' => ['current' => 'Old heading', 'proposed' => 'New heading'],
            'preview' => null,
        ]);
});

it('reports a nested entry shared by its owner’s revisions as unchanged', function () {
    $entry = comparisonEntry();
    travel(1)->minutes();
    $entry->setFieldValue('blocks', [
        'new1' => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'Heading']],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();
    latestRevision($entry);

    travel(1)->minutes();
    $entry->setFieldValue('summary', 'New summary');
    expect(Elements::saveElement($entry))->toBeTrue();
    $second = latestRevision($entry);

    $revisionBlock = Entry::find()
        ->ownerId($second->id)
        ->fieldId($this->blocksField->id)
        ->revisions(null)
        ->status(null)
        ->one();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revisionBlock->id,
        'ownerId' => $second->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $revisionBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($revisionBlock, 'heading'),
    ])->assertOk()
        ->assertJsonPath('hasBase', true)
        ->assertJsonPath('unchanged', true);
});

it('compares a nested entry added since the owner’s previous revision with nothing', function () {
    $entry = comparisonEntry();
    $first = latestRevision($entry);

    travel(1)->minutes();
    $entry->setFieldValue('blocks', [
        'new1' => ['type' => 'block', 'title' => 'Intro', 'fields' => ['heading' => 'Heading']],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();
    $second = latestRevision($entry);

    $revisionBlock = Entry::find()
        ->ownerId($second->id)
        ->fieldId($this->blocksField->id)
        ->revisions(null)
        ->status(null)
        ->one();

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revisionBlock->id,
        'ownerId' => $second->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $revisionBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($revisionBlock, 'heading'),
    ])->assertOk()
        ->assertJsonPath('fromLabel', $first->getRevisionLabel())
        ->assertJsonPath('hasBase', true)
        ->assertJsonPath('unchanged', false)
        ->assertJsonPath('diff', null)
        ->assertJsonPath('preview.current', null)
        ->assertJsonCount(1, 'preview.proposed.nodes');
});

it('compares HTML fields by their text, and previews ones that are still HTML or text with tags', function (string $handle, string $old, string $new, ?array $diff) {
    $entry = comparisonEntry();
    $entry->setFieldValue($handle, $old);
    expect(Elements::saveElement($entry))->toBeTrue();

    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id());
    $draft->setFieldValue($handle, $new);
    expect(Elements::saveElement($draft))->toBeTrue();

    $response = postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, $handle),
    ])->assertOk()
        ->assertJsonPath('diff', $diff);

    if ($diff === null) {
        $response->assertJsonCount(1, 'preview.current.nodes')->assertJsonCount(1, 'preview.proposed.nodes');
    }
})->with([
    'rich text' => ['body', '<p>Old</p><ul><li>One</li></ul>', '<p>New</p><ul><li>One</li></ul>', ['current' => "Old\n• One", 'proposed' => "New\n• One"]],
    'rich text markup only' => ['body', '<p>Same</p>', '<p><strong>Same</strong></p>', null],
    'rich text with tags once decoded' => ['body', '<p>Old</p>', '<p>&lt;img src=x onerror=alert(1)&gt;</p>', null],
    'plain text with tags' => ['summary', 'Old summary', '<img src=x onerror=alert(1)>', null],
    'options field' => ['color', 'red', 'blue', null],
]);

it('previews values that are still HTML once reduced to text, instead of diffing them', function () {
    $draft = draftWithSummary(comparisonEntry(), '<p>&lt;img src=x onerror=alert(1)&gt;</p>');

    postJson(action(CompareFieldChangesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('diff', null)
        ->assertJsonCount(1, 'preview.proposed.nodes');
});

it('offers to compare modified fields on drafts', function () {
    $entry = comparisonEntry();
    $draft = draftWithSummary($entry, 'New summary');

    expect(comparisonActionLabels($draft, 'summary'))->toContain(t('Compare changes'))
        ->and(comparisonActionLabels($draft, 'blocks'))->not->toContain(t('Compare changes'))
        ->and(comparisonActionLabels($entry, 'summary'))->not->toContain(t('Compare changes'));
});
