<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Http\Controllers\NestedElementsController;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

function nestedElementsControllerPayload(ElementInterface $owner, string $attribute): array
{
    return [
        'ownerElementType' => $owner::class,
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'attribute' => $attribute,
    ];
}

function nestedElementsControllerCreateMatrixOwnerFixture(): array
{
    $entryType = EntryTypeModel::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => true,
        ]);
    $ownerType = EntryTypeModel::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => true,
        ]);
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
    ];
}

function nestedElementsControllerCreateMatrixNestedEntry(
    EntryElement $owner,
    Matrix $field,
    EntryTypeModel $entryType,
    int $sortOrder,
    string $title,
    ?int $primaryOwnerId = null,
): EntryElement {
    $section = SectionModel::query()->findOrFail($owner->sectionId);
    $section->entryTypes()->syncWithoutDetaching([$entryType->id => ['sortOrder' => 2]]);

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->title($title)
        ->createElement([
            'fieldId' => $field->id,
            'primaryOwnerId' => $primaryOwnerId ?? $owner->id,
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

function nestedElementsControllerOwnerSortOrders(int $ownerId): array
{
    return DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $ownerId)
        ->orderBy('sortOrder')
        ->pluck('sortOrder', 'elementId')
        ->map(fn (mixed $sortOrder): int => (int) $sortOrder)
        ->all();
}

it('requires login for nested element routes', function () {
    auth()->logout();

    postJson(action([NestedElementsController::class, 'reorder']))->assertUnauthorized();
    postJson(action([NestedElementsController::class, 'destroy']))->assertUnauthorized();
});

it('validates the required owner params', function () {
    postJson(action([NestedElementsController::class, 'reorder']), [])
        ->assertJsonValidationErrors(['ownerElementType', 'ownerId', 'ownerSiteId', 'attribute']);
});

it('returns 400 for invalid owner params', function () {
    postJson(action([NestedElementsController::class, 'reorder']), [
        'ownerElementType' => User::class,
        'ownerId' => 999999,
        'ownerSiteId' => 1,
        'attribute' => 'addresses',
        'elementIds' => [],
        'offset' => 0,
    ])->assertStatus(400);
});

it('forbids nested element requests without session authorization', function () {
    $owner = User::findOne();

    postJson(action([NestedElementsController::class, 'reorder']), [
        ...nestedElementsControllerPayload($owner, 'addresses'),
        'elementIds' => [],
        'offset' => 0,
    ])->assertForbidden();
});

it('accepts canonical nested element authorization for provisional draft owners', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType] = nestedElementsControllerCreateMatrixOwnerFixture();

    $nestedEntry = nestedElementsControllerCreateMatrixNestedEntry($owner, $field, $entryType, 1, 'Draft block');

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($owner, auth()->id(), provisional: true);

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'field:matrixField')],
    ])->postJson(action([NestedElementsController::class, 'reorder']), [
        ...nestedElementsControllerPayload($draft, 'field:matrixField'),
        'elementIds' => [$nestedEntry->id],
        'offset' => 0,
    ])->assertOk();
});

it('validates reorder params', function () {
    $owner = User::findOne();

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'addresses')],
    ])->postJson(action([NestedElementsController::class, 'reorder']), nestedElementsControllerPayload($owner, 'addresses'))
        ->assertJsonValidationErrors(['elementIds', 'offset']);
});

it('reorders collection-backed nested elements', function () {
    $owner = User::findOne();
    $first = AddressModel::factory()->withOwnedElement($owner, 1)->createElement();
    $second = AddressModel::factory()->withOwnedElement($owner, 2)->createElement();
    $third = AddressModel::factory()->withOwnedElement($owner, 3)->createElement();

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'addresses')],
    ])->postJson(action([NestedElementsController::class, 'reorder']), [
        ...nestedElementsControllerPayload($owner, 'addresses'),
        'elementIds' => [$second->id],
        'offset' => 0,
    ])
        ->assertOk()
        ->assertJsonPath('message', t('New {total, plural, =1{position} other{positions}} saved.', ['total' => 1]));

    expect(nestedElementsControllerOwnerSortOrders($owner->id))->toBe([
        $second->id => 1,
        $first->id => 2,
        $third->id => 3,
    ]);
});

it('reorders query-backed nested elements', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType] = nestedElementsControllerCreateMatrixOwnerFixture();

    $first = nestedElementsControllerCreateMatrixNestedEntry($owner, $field, $entryType, 1, 'First');
    $second = nestedElementsControllerCreateMatrixNestedEntry($owner, $field, $entryType, 2, 'Second');
    $third = nestedElementsControllerCreateMatrixNestedEntry($owner, $field, $entryType, 3, 'Third');

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'field:matrixField')],
    ])->postJson(action([NestedElementsController::class, 'reorder']), [
        ...nestedElementsControllerPayload($owner, 'field:matrixField'),
        'elementIds' => [$second->id],
        'offset' => 0,
    ])->assertOk();

    expect(nestedElementsControllerOwnerSortOrders($owner->id))->toBe([
        $second->id => 1,
        $first->id => 2,
        $third->id => 3,
    ]);
});

it('validates destroy params', function () {
    $owner = User::findOne();

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'addresses')],
    ])->postJson(action([NestedElementsController::class, 'destroy']), nestedElementsControllerPayload($owner, 'addresses'))
        ->assertJsonValidationErrors(['elementId']);
});

it('returns 400 when the nested element cannot be found', function () {
    $owner = User::findOne();

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'addresses')],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        ...nestedElementsControllerPayload($owner, 'addresses'),
        'elementId' => 999999,
    ])->assertStatus(400);
});

it('deletes only the ownership when the nested element primarily belongs to another owner', function () {
    [
        'owner' => $owner,
        'field' => $field,
        'entryType' => $entryType,
        'ownerType' => $ownerType,
        'section' => $section,
    ] = nestedElementsControllerCreateMatrixOwnerFixture();

    $primaryOwner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement();
    $nestedEntry = nestedElementsControllerCreateMatrixNestedEntry(
        $owner,
        $field,
        $entryType,
        1,
        'Shared block',
        $primaryOwner->id,
    );

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'field:matrixField')],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        ...nestedElementsControllerPayload($owner, 'field:matrixField'),
        'elementId' => $nestedEntry->id,
    ])
        ->assertOk()
        ->assertJsonPath('message', t('{type} deleted.', ['type' => EntryElement::displayName()]));

    expect(DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $owner->id)
        ->where('elementId', $nestedEntry->id)
        ->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $nestedEntry->id)->value('dateDeleted'))
        ->toBeNull();
});

it('returns a failure response when deleting a primary nested element fails', function () {
    $owner = User::find()->id(User::findOne()->id)->one();
    $address = AddressModel::factory()->withOwnedElement($owner, 1)->createElement();

    Event::listen(BeforeDelete::class, function (BeforeDelete $event) use ($address) {
        if ($event->element->id !== $address->id) {
            return;
        }

        $event->isValid = false;
    });

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'addresses')],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        ...nestedElementsControllerPayload($owner, 'addresses'),
        'elementId' => $address->id,
    ])
        ->assertStatus(400)
        ->assertJsonPath('message', t('Couldn’t delete {type}.', ['type' => $address::lowerDisplayName()]));
});

it('deletes query-backed nested elements that primarily belong to the owner', function () {
    ['owner' => $owner, 'field' => $field, 'entryType' => $entryType] = nestedElementsControllerCreateMatrixOwnerFixture();

    $nestedEntry = nestedElementsControllerCreateMatrixNestedEntry($owner, $field, $entryType, 1, 'Block');

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, 'field:matrixField')],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        ...nestedElementsControllerPayload($owner, 'field:matrixField'),
        'elementId' => $nestedEntry->id,
    ])
        ->assertOk()
        ->assertJsonPath('message', t('{type} deleted.', ['type' => EntryElement::displayName()]));

    expect(DB::table(Table::ELEMENTS)->where('id', $nestedEntry->id)->value('dateDeleted'))
        ->not->toBeNull();
});
