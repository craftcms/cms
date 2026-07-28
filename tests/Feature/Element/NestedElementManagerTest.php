<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

function createMatrixEntryType(): EntryTypeModel
{
    return EntryTypeModel::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => true,
        ]);
}

function createMatrixOwnerFixture(array $fieldSettings = []): array
{
    $entryType = createMatrixEntryType();
    $ownerType = createMatrixEntryType();
    $section = SectionModel::factory()->withEntryTypes($ownerType, $entryType)->create();
    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->withField('matrixField', Matrix::class, [...$fieldSettings, 'entryTypes' => [$entryType->id]])
        ->createElementWithFields();

    Sections::refreshSections();

    /** @var Matrix $field */
    $field = Fields::getFieldById($result->field('matrixField')->id);

    return [
        'owner' => EntryElement::find()->id($result->element->id)->one(),
        'field' => $field,
        'entryType' => $entryType,
        'ownerType' => $ownerType,
        'section' => $section,
        'manager' => createMatrixManager($field),
    ];
}

function createMatrixNestedEntry(EntryElement $owner, Matrix $field, EntryTypeModel $entryType, int $sortOrder, string $title, ?SectionModel $section = null): EntryElement
{
    // Nested owners (e.g. a Matrix block used as the owner of a further-nested Matrix field)
    // have no sectionId of their own, so the section must be passed in explicitly for them.
    $section ??= SectionModel::query()->findOrFail($owner->sectionId);
    $section->entryTypes()->syncWithoutDetaching([$entryType->id => ['sortOrder' => 2]]);

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->title($title)
        ->createElement([
            'fieldId' => $field->id,
            'primaryOwnerId' => $owner->id,
        ]);

    DB::table(Table::ENTRIES)
        ->where('id', $entry->id)
        ->update(['sectionId' => null]);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $entry->id,
        'ownerId' => $owner->id,
        'sortOrder' => $sortOrder,
    ]);

    Sections::refreshSections();

    return EntryElement::find()->id($entry->id)->status(null)->one();
}

function nestedOwnerSortOrders(int $ownerId): array
{
    return DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $ownerId)
        ->orderBy('sortOrder')
        ->pluck('sortOrder', 'elementId')
        ->map(fn (mixed $sortOrder): int => (int) $sortOrder)
        ->all();
}

function createMatrixManager(Matrix $field): NestedElementManager
{
    return new NestedElementManager(
        EntryElement::class,
        fn (ElementInterface $owner) => createMatrixQuery($field, $owner),
        [
            'field' => $field,
            'criteria' => [
                'fieldId' => $field->id,
            ],
            'propagationMethod' => $field->propagationMethod,
            'propagationKeyFormat' => $field->propagationKeyFormat,
        ],
    );
}

function createMatrixQuery(Matrix $field, ?ElementInterface $owner): EntryQuery
{
    $query = EntryElement::find()
        ->fieldId($field->id)
        ->siteId($owner->siteId ?? null);

    if ($owner && $owner->id) {
        $query->ownerId = $owner->id;
        $query->primaryOwnerId = $owner->id;

        if ($owner->getIsRevision()) {
            $query
                ->revisions(null)
                ->trashed(null);
        }
    } else {
        $query->id = false;
    }

    return $query;
}

it('saves matrix nested entries in collection order and deletes detached entries', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'manager' => $manager] = createMatrixOwnerFixture();

    $first = createMatrixNestedEntry($owner, $field, $entryType, 1, 'First');
    $second = createMatrixNestedEntry($owner, $field, $entryType, 2, 'Second');

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$second, $first]);
    $owner->setFieldValue($field->handle, $query);

    $manager->maintainNestedElements($owner, false);

    expect(nestedOwnerSortOrders($owner->id))->toBe([
        $second->id => 1,
        $first->id => 2,
    ]);

    $owner = EntryElement::find()->id($owner->id)->one();
    $second = EntryElement::find()->id($second->id)->status(null)->one();

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$second]);
    $owner->setFieldValue($field->handle, $query);

    $manager->maintainNestedElements($owner, false);

    expect(nestedOwnerSortOrders($owner->id))->toBe([
        $second->id => 1,
        $first->id => 2,
    ])
        ->and(DB::table(Table::ELEMENTS_OWNERS)
            ->where('ownerId', $owner->id)
            ->where('elementId', $second->id)
            ->value('sortOrder'))
        ->toBe(1)
        ->and(DB::table(Table::ELEMENTS)->where('id', $first->id)->value('dateDeleted'))->not->toBeNull()
        ->and(DB::table(Table::ELEMENTS)->where('id', $second->id)->value('dateDeleted'))->toBeNull();
});

it('duplicates matrix nested entries between owners', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'ownerType' => $ownerType, 'section' => $section, 'manager' => $manager] = createMatrixOwnerFixture();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Canonical block');
    $target = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();
    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$nested]);
    $owner->setFieldValue($field->handle, $query);

    $manager->duplicateNestedElements($owner, $target);

    $targetNestedId = DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $target->id)
        ->value('elementId');

    /** @var EntryElement $targetNested */
    $targetNested = EntryElement::find()
        ->id($targetNestedId)
        ->status(null)
        ->one();

    expect($targetNested)->not->toBeNull()
        ->and($targetNested->id)->not->toBe($nested->id)
        ->and($targetNested->getPrimaryOwnerId())->toBe($target->id)
        ->and($targetNested->fieldId)->toBe($field->id)
        ->and(DB::table(Table::ELEMENTS_OWNERS)
            ->where('ownerId', $target->id)
            ->where('elementId', $targetNested->id)
            ->value('sortOrder'))
        ->toBe(1);
});

it('creates matrix nested revisions for a target owner', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'ownerType' => $ownerType, 'section' => $section, 'manager' => $manager] = createMatrixOwnerFixture();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Revision block');
    $target = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();

    $method = new ReflectionMethod($manager, 'createRevisions');
    $method->invoke($manager, $owner, $target);

    $revisionNestedId = DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $target->id)
        ->value('elementId');

    /** @var EntryElement $revisionNested */
    $revisionNested = EntryElement::find()
        ->revisions()
        ->id($revisionNestedId)
        ->status(null)
        ->one();

    expect($revisionNested)->not->toBeNull()
        ->and($revisionNested->getCanonicalId())->toBe($nested->id)
        ->and($revisionNested->getPrimaryOwnerId())->toBe($target->id)
        ->and(DB::table(Table::ELEMENTS_OWNERS)
            ->where('ownerId', $target->id)
            ->where('elementId', $revisionNested->id)
            ->value('sortOrder'))
        ->toBe(1)
        ->and($revisionNested->revisionId)
        ->not->toBeNull()
        ->and(DB::table(Table::REVISIONS)->where('id', $revisionNested->revisionId)->exists())
        ->toBeTrue();
});

it('saves nested provisional draft ownership for derivative and primary owners', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType] = createMatrixOwnerFixture();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Canonical block');
    $ownerDraft = app(Drafts::class)->createDraft($owner, auth()->id(), provisional: true);
    $nestedDraft = app(Drafts::class)->createDraft(
        $nested,
        auth()->id(),
        name: 'Nested provisional draft',
        newAttributes: [
            'ownerId' => $ownerDraft->id,
            'primaryOwnerId' => $owner->id,
        ],
        provisional: true,
    );

    $ownership = DB::table(Table::ELEMENTS_OWNERS)
        ->where('elementId', $nestedDraft->id)
        ->pluck('sortOrder', 'ownerId')
        ->map(fn (mixed $sortOrder): int => (int) $sortOrder)
        ->all();

    expect(array_keys($ownership))->toEqualCanonicalizing([$owner->id, $ownerDraft->id])
        ->and($ownership[$owner->id])->toBe(1)
        ->and($ownership[$ownerDraft->id])->toBe(1)
        ->and(EntryElement::find()
            ->id($nestedDraft->id)
            ->ownerId($owner->id)
            ->primaryOwnerId($owner->id)
            ->drafts()
            ->provisionalDrafts()
            ->status(null)
            ->one())
        ->not->toBeNull()
        ->and(EntryElement::find()
            ->id($nestedDraft->id)
            ->ownerId($ownerDraft->id)
            ->primaryOwnerId($owner->id)
            ->drafts()
            ->provisionalDrafts()
            ->status(null)
            ->one())
        ->not->toBeNull();
});

it('deletes and restores attribute-backed user addresses with owner state', function () {
    $user = UserModel::factory()->createElement();
    $address = AddressModel::factory()->createElement([
        'primaryOwnerId' => $user->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $address->id,
        'ownerId' => $user->id,
        'sortOrder' => 1,
    ]);

    $user->getAddressManager()->deleteNestedElements($user);

    expect((bool) DB::table(Table::ELEMENTS)->where('id', $address->id)->value('deletedWithOwner'))->toBeTrue()
        ->and(DB::table(Table::ELEMENTS)->where('id', $address->id)->value('dateDeleted'))->not->toBeNull();

    $user->getAddressManager()->restoreNestedElements($user);

    /** @var AddressElement $restoredAddress */
    $restoredAddress = AddressElement::find()
        ->id($address->id)
        ->status(null)
        ->one();

    expect($restoredAddress)->not->toBeNull()
        ->and(DB::table(Table::ELEMENTS)->where('id', $address->id)->value('deletedWithOwner'))->toBeNull()
        ->and(DB::table(Table::ELEMENTS)->where('id', $address->id)->value('dateDeleted'))->toBeNull();
});

it('applies draft-owned field addresses to canonical entries', function () {
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->create();
    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withField('addressesField', Addresses::class)
        ->createElementWithFields();

    Sections::refreshSections();

    /** @var Addresses $field */
    $field = Fields::getFieldById($result->field('addressesField')->id);
    /** @var EntryElement $entry */
    $entry = EntryElement::find()->id($result->element->id)->one();
    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

    $address = AddressModel::factory()
        ->withOwnedElement($draft, 1)
        ->createElement([
            'fieldId' => $field->id,
            'countryCode' => 'US',
            'addressLine1' => '123 Main St',
        ]);

    app(Drafts::class)->applyDraft($draft);

    /** @var EntryElement $canonical */
    $canonical = EntryElement::find()->id($entry->id)->one();
    $addresses = $canonical->getFieldValue($field->handle)->status(null)->all();

    expect($addresses)->toHaveCount(1)
        ->and($addresses[0]->id)->not->toBe($address->id)
        ->and($addresses[0]->getPrimaryOwnerId())->toBe($canonical->id)
        ->and($addresses[0]->addressLine1)->toBe('123 Main St');

    expect(AddressElement::find()->id($address->id)->drafts(null)->status(null)->one())->toBeNull();
});

it('scopes field addresses to their draft owner', function () {
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->create();
    $firstResult = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withField('addressesField', Addresses::class)
        ->createElementWithFields();

    Sections::refreshSections();

    /** @var Addresses $field */
    $field = Fields::getFieldById($firstResult->field('addressesField')->id);
    /** @var EntryElement $firstEntry */
    $firstEntry = EntryElement::find()->id($firstResult->element->id)->one();
    /** @var EntryElement $firstDraft */
    $firstDraft = app(Drafts::class)->createDraft($firstEntry, auth()->id(), provisional: true);
    $secondEntry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement();
    /** @var EntryElement $secondDraft */
    $secondDraft = app(Drafts::class)->createDraft($secondEntry, auth()->id(), provisional: true);

    AddressModel::factory()
        ->withOwnedElement($firstDraft, 1)
        ->createElement([
            'fieldId' => $field->id,
            'countryCode' => 'US',
            'addressLine1' => '123 Main St',
        ]);

    $firstAddresses = $firstDraft->getFieldValue($field->handle)->status(null)->all();
    $secondAddresses = $secondDraft->getFieldValue($field->handle)->status(null)->all();

    expect($firstAddresses)->toHaveCount(1)
        ->and($secondAddresses)->toHaveCount(0);
});

it('returns blank ui labels for titleless nested entries with empty ui label formats', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType] = createMatrixOwnerFixture();

    $entryType->update([
        'hasTitleField' => false,
        'titleFormat' => null,
        'uiLabelFormat' => '',
    ]);
    app(EntryTypes::class)->refreshEntryTypes();
    $field->setEntryTypes([$entryType->id]);

    $entry = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Nested title');

    expect($entry->getUiLabel())->toBe('');
});

it('eager-loads matrix entries for the requested source owner', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'ownerType' => $ownerType, 'section' => $section] = createMatrixOwnerFixture();

    $firstNested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'First owner block');
    $secondOwner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();
    createMatrixNestedEntry($secondOwner, $field, $entryType, 1, 'Second owner block');

    app(Elements::class)->eagerLoadElements(EntryElement::class, [$owner, $secondOwner], [$field->handle]);

    $eagerLoaded = $owner->getFieldValue($field->handle)->all();

    expect($eagerLoaded)->toHaveCount(1)
        ->and($eagerLoaded[0]->id)->toBe($firstNested->id)
        ->and($eagerLoaded[0]->getPrimaryOwnerId())->toBe($owner->id);
});

it('creates eager-loaded field addresses with the requested source owner', function () {
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->create();
    $firstResult = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withField('addressesField', Addresses::class)
        ->createElementWithFields();

    Sections::refreshSections();

    /** @var Addresses $field */
    $field = Fields::getFieldById($firstResult->field('addressesField')->id);
    /** @var EntryElement $firstEntry */
    $firstEntry = EntryElement::find()->id($firstResult->element->id)->one();
    $address = AddressModel::factory()
        ->withOwnedElement($firstEntry, 1)
        ->createElement([
            'fieldId' => $field->id,
            'countryCode' => 'US',
            'addressLine1' => '123 Main St',
        ]);
    $map = $field->getEagerLoadingMap([$firstEntry]);
    $query = Mockery::mock(AddressQuery::class);
    $result = [
        'id' => $address->id,
        'siteId' => $firstEntry->siteId,
    ];

    $query->shouldReceive('owner')
        ->once()
        ->with($firstEntry)
        ->andReturnSelf();
    $query->shouldReceive('createElement')
        ->once()
        ->with($result)
        ->andReturn($address);

    $created = $map['createElement']($query, $result, $firstEntry);

    expect($created)->toBe($address);
});

// Regression coverage ported from craftcms/cms commit 2df11c4b (NestedElementManager tests).
// Guards a set of historical bugs; each is annotated with what it exercises in this codebase.

it('reports translatability for Custom propagation without throwing when no key format is set', function () {
    $manager = new NestedElementManager(
        AddressElement::class,
        fn () => AddressElement::find(),
        [
            'attribute' => 'addresses',
            'propagationMethod' => PropagationMethod::Custom,
            'propagationKeyFormat' => null,
        ],
    );

    $owner = UserModel::factory()->createElement();

    expect($manager->getIsTranslatable($owner))->toBeTrue()
        ->and($manager->getIsTranslatable())->toBeTrue();
});

it('includes nested entry field content in search keywords', function () {
    $textFieldModel = Field::factory()->create([
        'name' => 'Text',
        'handle' => 'nemText',
        'type' => PlainText::class,
        'searchable' => true,
    ]);
    $entryType = EntryTypeModel::factory()
        ->withField($textFieldModel)
        ->create(['name' => 'Block', 'handle' => 'block', 'hasTitleField' => true]);
    $ownerType = createMatrixEntryType();
    $section = SectionModel::factory()->withEntryTypes($ownerType, $entryType)->create();
    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$entryType->id]])
        ->createElementWithFields();

    Sections::refreshSections();
    Fields::refreshFields();

    /** @var Matrix $field */
    $field = Fields::getFieldById($result->field('matrixField')->id);
    $owner = EntryElement::find()->id($result->element->id)->one();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Block', $section);
    $nested->setFieldValue('nemText', 'FindThisUniqueString');
    app(Elements::class)->saveElement($nested);

    $manager = createMatrixManager($field);
    $keywords = $manager->getSearchKeywords($owner);

    expect($keywords)->toContain('FindThisUniqueString');
});

it('keeps a deleted nested entry deleted after resaving the owner with no changes', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'manager' => $manager] = createMatrixOwnerFixture();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Only block');

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([]);
    $owner->setFieldValue($field->handle, $query);
    $manager->maintainNestedElements($owner, false);

    expect(DB::table(Table::ELEMENTS)->where('id', $nested->id)->value('dateDeleted'))->not->toBeNull();

    // Resave again with the same already-fetched empty result. Guards the intent of the historical
    // "!$query->getCachedResult() treats an already-fetched empty array as unfetched" fix; in this
    // port both the query layer's own null-check (getModels()) and this class's `$elements !== null`
    // check are already strict, so reverting the local check alone doesn't reproduce a failure here -
    // this is general coverage for "deleted stays deleted", not a proof of one specific line.
    $owner2 = EntryElement::find()->id($owner->id)->one();
    $query2 = createMatrixQuery($field, $owner2);
    $query2->setResultOverride([]);
    $owner2->setFieldValue($field->handle, $query2);
    $manager->maintainNestedElements($owner2, false);

    expect(DB::table(Table::ELEMENTS)->where('id', $nested->id)->value('dateDeleted'))->not->toBeNull();
});

it('keeps a new nested entry owned when added while the owner is resaving', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'manager' => $manager] = createMatrixOwnerFixture();

    $existing = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Original block');

    $new = new EntryElement([
        'fieldId' => $field->id,
        'typeId' => $entryType->id,
        'title' => 'Added during resave',
        'siteId' => $owner->siteId,
    ]);

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$existing, $new]);
    $owner->setFieldValue($field->handle, $query);
    $owner->resaving = true;

    $manager->maintainNestedElements($owner, false);

    $added = EntryElement::find()
        ->fieldId($field->id)
        ->ownerId($owner->id)
        ->title('Added during resave')
        ->status(null)
        ->one();

    expect($added)->not->toBeNull()
        ->and(DB::table(Table::ELEMENTS_OWNERS)
            ->where('elementId', $added->id)
            ->where('ownerId', $owner->id)
            ->exists())
        ->toBeTrue();
});

// General coverage for order-preserving duplication (historically fixed by e5d6cd8/3b0ede5).
// sortOrder is derived from more than one place in this port's duplicateNestedElements(), so
// blanking the explicit `sortOrder` attribute alone didn't reproduce a failure here.
it('preserves nested entry sort order across three duplicated entries', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'ownerType' => $ownerType, 'section' => $section, 'manager' => $manager] = createMatrixOwnerFixture();

    $first = createMatrixNestedEntry($owner, $field, $entryType, 1, 'First');
    $second = createMatrixNestedEntry($owner, $field, $entryType, 2, 'Second');
    $third = createMatrixNestedEntry($owner, $field, $entryType, 3, 'Third');

    $target = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$first, $second, $third]);
    $owner->setFieldValue($field->handle, $query);

    $manager->duplicateNestedElements($owner, $target);

    $duplicated = EntryElement::find()
        ->fieldId($field->id)
        ->ownerId($target->id)
        ->status(null)
        ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
        ->all();

    expect($duplicated)->toHaveCount(3)
        ->and(array_map(fn (EntryElement $e) => $e->title, $duplicated))->toBe(['First', 'Second', 'Third']);
});

it('ignores in-memory unsaved elements without an id when duplicating', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'ownerType' => $ownerType, 'section' => $section, 'manager' => $manager] = createMatrixOwnerFixture();

    $saved = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Saved block');

    $unsaved = new EntryElement([
        'fieldId' => $field->id,
        'typeId' => $entryType->id,
    ]);
    expect($unsaved->id)->toBeNull();

    $target = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();

    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$saved, $unsaved]);
    $owner->setFieldValue($field->handle, $query);

    $manager->duplicateNestedElements($owner, $target);

    $duplicated = EntryElement::find()
        ->fieldId($field->id)
        ->ownerId($target->id)
        ->status(null)
        ->all();

    expect($duplicated)->toHaveCount(1)
        ->and($duplicated[0]->title)->toBe('Saved block');
});

// Models the historical "repeatedly editing a nested element inside a draft, then applying the
// draft, churns the nested element's canonical ID" bug class (2-level-nested Matrix, matching the
// original report). This reproduces the scenario end-to-end; it's general regression coverage for
// the bug class rather than a proof that reverting any single line breaks it.
it('keeps the same canonical id after repeatedly editing a nested draft entry then applying it', function () {
    // Leaf block entry type (used for the innermost, 2nd-level nested entry).
    $leafEntryType = createMatrixEntryType();

    // Inner Matrix field + the entry type that owns it (used for the 1st-level nested entry).
    $innerFieldModel = Field::factory()->create([
        'name' => 'Inner Matrix',
        'handle' => 'innerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$leafEntryType->id]],
    ]);
    $middleEntryType = EntryTypeModel::factory()
        ->withField($innerFieldModel)
        ->create(['name' => 'BlockWithInner', 'handle' => 'blockWithInner', 'hasTitleField' => true]);

    $ownerType = createMatrixEntryType();
    $section = SectionModel::factory()->withEntryTypes($ownerType, $middleEntryType, $leafEntryType)->create();

    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$middleEntryType->id]])
        ->createElementWithFields();

    Sections::refreshSections();
    Fields::refreshFields();

    /** @var Matrix $field */
    $field = Fields::getFieldById($result->field('matrixField')->id);
    /** @var Matrix $innerField */
    $innerField = Fields::getFieldById($innerFieldModel->id);
    $owner = EntryElement::find()->id($result->element->id)->one();

    $blockA = createMatrixNestedEntry($owner, $field, $middleEntryType, 1, 'A', $section);
    $blockB = createMatrixNestedEntry($blockA, $innerField, $leafEntryType, 1, 'v1', $section);
    $originalNestedId = $blockB->id;

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($owner, auth()->id());
    $draftBlockA = EntryElement::find()->fieldId($field->id)->ownerId($draft->id)->status(null)->one();

    $draftBlockB = EntryElement::find()->fieldId($innerField->id)->ownerId($draftBlockA->id)->status(null)->one();
    $draftBlockA->setFieldValue('innerMatrix', [
        $draftBlockB->id => ['title' => 'v2'],
    ]);
    app(Elements::class)->saveElement($draftBlockA);

    $draftBlockB = EntryElement::find()->fieldId($innerField->id)->ownerId($draftBlockA->id)->status(null)->one();
    $draftBlockA->setFieldValue('innerMatrix', [
        $draftBlockB->id => ['title' => 'v3'],
    ]);
    app(Elements::class)->saveElement($draftBlockA);

    app(Drafts::class)->applyDraft($draft);

    $owner = EntryElement::find()->id($owner->id)->one();
    $finalBlockA = EntryElement::find()->fieldId($field->id)->ownerId($owner->id)->status(null)->one();
    $finalBlockB = EntryElement::find()->fieldId($innerField->id)->ownerId($finalBlockA->id)->status(null)->one();

    expect($finalBlockB->id)->toBe($originalNestedId)
        ->and($finalBlockB->title)->toBe('v3');
});

it('duplicates nested entries into a new site without losing existing site content', function () {
    // A more restrictive propagation method than the owner's is required to exercise the
    // "other sites" duplication branch in NestedElementManager::saveNestedElements().
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'section' => $section, 'manager' => $manager] = createMatrixOwnerFixture(['propagationMethod' => 'none']);

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondSite->id,
        'hasUrls' => true,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);
    Sections::refreshSections();

    $nested = createMatrixNestedEntry($owner, $field, $entryType, 1, 'Default site content');

    app(Elements::class)->propagateElement($owner, $secondSite->id);

    $ownerInNewSite = EntryElement::find()->id($owner->id)->siteId($secondSite->id)->status(null)->one();
    expect($ownerInNewSite)->not->toBeNull();

    // Mark the new site as newly added on the default-site owner - this is what tells
    // NestedElementManager::saveNestedElements() to duplicate the field's content into it.
    // The manager needs a field instance resolved through the owner's field layout (so
    // `layoutElement` is populated) rather than a bare `Fields::getFieldById()` lookup, since
    // `propagateRequired()` dereferences it unconditionally in the "other sites" branch.
    $layoutField = $owner->getFieldLayout()->getFieldByHandle($field->handle);
    $manager = createMatrixManager($layoutField);

    $owner->newSiteIds = [$secondSite->id];
    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([$nested]);
    $owner->setFieldValue($field->handle, $query);
    $owner->setDirtyFields([$field->handle]);
    $manager->maintainNestedElements($owner, false);

    $defaultSiteNested = EntryElement::find()->fieldId($field->id)->ownerId($owner->id)->siteId($owner->siteId)->status(null)->all();
    $newSiteNested = EntryElement::find()->fieldId($field->id)->ownerId($ownerInNewSite->id)->siteId($secondSite->id)->status(null)->all();

    expect($defaultSiteNested)->toHaveCount(1)
        ->and($defaultSiteNested[0]->title)->toBe('Default site content')
        ->and($newSiteNested)->toHaveCount(1)
        ->and($newSiteNested[0]->title)->toBe('Default site content')
        ->and($newSiteNested[0]->id)->not->toBe($defaultSiteNested[0]->id);
});

it('restores a nested entry when reverting to an old revision without an integrity error', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType, 'manager' => $manager] = createMatrixOwnerFixture();

    createMatrixNestedEntry($owner, $field, $entryType, 1, 'Revision 1 content');

    $revisionId = app(Revisions::class)->createRevision($owner);
    $v1 = EntryElement::find()->id($revisionId)->revisions()->status(null)->one();
    expect($v1)->not->toBeNull();

    // Remove the nested block and save again, creating a second state without it
    $query = createMatrixQuery($field, $owner);
    $query->setResultOverride([]);
    $owner->setFieldValue($field->handle, $query);
    $manager->maintainNestedElements($owner, false);

    // Reverting to the first revision must not throw, and must bring the nested block back
    app(Revisions::class)->revertToRevision($v1, auth()->id());

    $owner = EntryElement::find()->id($owner->id)->one();
    $restoredNested = EntryElement::find()->fieldId($field->id)->ownerId($owner->id)->status(null)->all();

    expect($restoredNested)->toHaveCount(1)
        ->and($restoredNested[0]->title)->toBe('Revision 1 content');
});
