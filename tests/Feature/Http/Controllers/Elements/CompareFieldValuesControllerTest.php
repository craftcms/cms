<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\CompareFieldValuesController;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns 400 for canonical elements', function () {
    $entry = comparisonEntry();

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'layoutElementUid' => comparisonFieldUid($entry, 'summary'),
    ])->assertBadRequest();
});

it('validates the request payload', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'status' => 'nope',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['layoutElementUid', 'status']);
});

it('returns 400 for invalid layout element uuids', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldValuesController::class), [
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertForbidden();
});

it('compares a draft with the current entry', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary');

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Current'), 'value' => 'Old summary'],
            'to' => ['label' => $draft->draftName, 'value' => 'New summary'],
            'hasBase' => true,
            'unchanged' => false,
        ]);
});

it('calls a provisional draft the user’s changes', function () {
    $draft = draftWithSummary(comparisonEntry(), 'New summary', provisional: true);

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('to.label', t('Your changes'))
        ->assertJsonPath('to.value', 'New summary');
});

it('reports values that are the same on both sides', function () {
    $draft = draftWithSummary(comparisonEntry(), 'Old summary');

    postJson(action(CompareFieldValuesController::class), [
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $second->id,
        'siteId' => $second->siteId,
        'layoutElementUid' => comparisonFieldUid($second, 'summary'),
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Revision {num}', ['num' => $first->revisionNum]), 'value' => 'First summary'],
            'to' => ['label' => t('Revision {num}', ['num' => $second->revisionNum]), 'value' => 'Second summary'],
            'hasBase' => true,
            'unchanged' => false,
        ]);
});

it('has nothing to compare an entry’s first revision with', function () {
    $revision = Entry::find()
        ->revisionOf(comparisonEntry())
        ->status(null)
        ->orderBy('revisions.num')
        ->one();

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revision->id,
        'siteId' => $revision->siteId,
        'layoutElementUid' => comparisonFieldUid($revision, 'summary'),
    ])->assertOk()
        ->assertJsonPath('from.label', t('No earlier revision'))
        ->assertJsonPath('from.value', null)
        ->assertJsonPath('hasBase', false);
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
        'status' => 'outdated',
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Revision {num}', ['num' => $before->revisionNum]), 'value' => 'Old summary'],
            'to' => ['label' => t('Current'), 'value' => 'Updated summary'],
            'hasBase' => true,
            'unchanged' => false,
        ]);
});

it('compares a field in a nested entry with the current entry’s', function () {
    [$draft, $draftBlock] = draftWithEditedBlock();

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draftBlock->id,
        'ownerId' => $draft->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $draftBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($draftBlock, 'heading'),
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Current'), 'value' => 'Old heading'],
            'to' => ['label' => $draft->draftName, 'value' => 'New heading'],
            'hasBase' => true,
            'unchanged' => false,
        ]);
});

it('lists nested entries’ fields when comparing the field that holds them', function () {
    [$draft] = draftWithEditedBlock();

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'blocks'),
    ])->assertOk()
        ->assertJsonPath('from.value', "Block\nTitle: Intro\nHeading: Old heading")
        ->assertJsonPath('to.value', "Block\nTitle: Intro\nHeading: New heading");
});

it('starts the lines of entries nested further down with the path to them', function () {
    $childType = EntryType::factory()->create(['name' => 'Child', 'handle' => 'child', 'hasTitleField' => true]);
    $innerField = Field::factory()->create([
        'name' => 'Inner blocks',
        'handle' => 'innerBlocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$childType->id]],
    ]);
    Fields::refreshFields();

    $sectionType = EntryType::factory()
        ->withField($innerField)
        ->create(['name' => 'Section', 'handle' => 'section', 'hasTitleField' => true]);
    $outerField = Field::factory()->create([
        'name' => 'Sections',
        'handle' => 'sections',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$sectionType->id]],
    ]);
    Fields::refreshFields();

    $pageType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $this->summaryField->uid]),
            new CustomField(config: ['fieldUid' => $outerField->uid]),
        ]))
        ->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();

    $page = EntryModel::factory()
        ->forSection(Section::factory()->withEntryTypes($pageType)->create(['handle' => 'pages', 'type' => SectionType::Channel]))
        ->forEntryType($pageType)
        ->createElement(['title' => 'Page']);
    $page->setFieldValue('sections', [
        'new1' => [
            'type' => 'section',
            'title' => 'Intro',
            'fields' => [
                'innerBlocks' => [
                    'new1' => ['type' => 'child', 'title' => 'First child'],
                    'new2' => ['type' => 'child', 'title' => 'Second child', 'enabled' => false],
                ],
            ],
        ],
    ]);
    expect(Elements::saveElement($page))->toBeTrue();

    $draft = draftWithSummary($page, 'New summary');

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'sections'),
    ])->assertOk()
        ->assertJsonPath('from.value', implode("\n", [
            'Section',
            'Title: Intro',
            'Inner blocks › Child 1 › Title: First child',
            'Inner blocks › Child 2 (Disabled) › Title: Second child',
        ]));
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revisionBlock->id,
        'ownerId' => $second->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $revisionBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($revisionBlock, 'heading'),
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Revision {num}', ['num' => $first->revisionNum]), 'value' => 'Old heading'],
            'to' => ['label' => t('Revision {num}', ['num' => $second->revisionNum]), 'value' => 'New heading'],
            'hasBase' => true,
            'unchanged' => false,
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

    postJson(action(CompareFieldValuesController::class), [
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

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $revisionBlock->id,
        'ownerId' => $second->id,
        'fieldId' => $this->blocksField->id,
        'siteId' => $revisionBlock->siteId,
        'layoutElementUid' => comparisonFieldUid($revisionBlock, 'heading'),
    ])->assertOk()
        ->assertExactJson([
            'from' => ['label' => t('Revision {num}', ['num' => $first->revisionNum]), 'value' => null],
            'to' => ['label' => t('Revision {num}', ['num' => $second->revisionNum]), 'value' => 'Heading'],
            'hasBase' => true,
            'unchanged' => false,
        ]);
});

it('compares rich text by its text', function () {
    $draft = draftWithSummary(comparisonEntry('<p>Old</p><ul><li>One</li></ul>'), '<p>New</p><ul><li>One</li></ul>');

    postJson(action(CompareFieldValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'layoutElementUid' => comparisonFieldUid($draft, 'summary'),
    ])->assertOk()
        ->assertJsonPath('from.value', "Old\n• One")
        ->assertJsonPath('to.value', "New\n• One");
});

it('offers to compare modified fields on drafts', function () {
    $entry = comparisonEntry();
    $draft = draftWithSummary($entry, 'New summary');

    expect(comparisonActionLabels($draft, 'summary'))->toContain(t('Compare changes'))
        ->and(comparisonActionLabels($draft, 'blocks'))->not->toContain(t('Compare changes'))
        ->and(comparisonActionLabels($entry, 'summary'))->not->toContain(t('Compare changes'));
});
