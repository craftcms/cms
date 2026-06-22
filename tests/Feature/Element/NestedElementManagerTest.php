<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
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

function createMatrixOwnerFixture(): array
{
    $entryType = createMatrixEntryType();
    $ownerType = createMatrixEntryType();
    $section = SectionModel::factory()->withEntryTypes($ownerType, $entryType)->create();
    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$entryType->id]])
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

function createMatrixNestedEntry(EntryElement $owner, Matrix $field, EntryTypeModel $entryType, int $sortOrder, string $title): EntryElement
{
    $section = SectionModel::query()->findOrFail($owner->sectionId);
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
