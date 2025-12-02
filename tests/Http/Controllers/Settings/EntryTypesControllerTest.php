<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Models\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

    $this->entryTypes = resolve(EntryTypes::class);

    EntryType::factory()->create();
});

it('requires authentication', function () {
    Auth::logout();

    get(action([EntryTypesController::class, 'index']))->assertRedirect();
    get(action([EntryTypesController::class, 'create']))->assertRedirect();
    get(action([EntryTypesController::class, 'edit'], [EntryType::first()->id]))->assertRedirect();
    get(action([EntryTypesController::class, 'tableData']))->assertRedirect();
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'store']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'destroy']))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([EntryTypesController::class, 'edit'], [EntryType::first()->id]))->assertSee(t('Changes to these settings aren’t permitted in this environment.'));

    // Not allowed
    get(action([EntryTypesController::class, 'create']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'store']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'destroy']))->assertForbidden();
});

test('index can be loaded', function () {
    get(action([EntryTypesController::class, 'index']))
        ->assertOk();
});

test('create can be loaded', function () {
    get(action([EntryTypesController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new entry type'));
});

test('it can edit an entry type', function () {
    $entryType = $this->entryTypes->getEntryTypeById(EntryType::first()->id);

    get(action([EntryTypesController::class, 'edit'], [$entryType->id]))
        ->assertOk()
        ->assertSee($entryType->name);
});

it('404s when an entry type does not exist', function () {
    get(action([EntryTypesController::class, 'edit'], [999]))
        ->assertNotFound();
});

function validEntryTypeData(array $overrides = []): array
{
    return array_merge([
        'name' => 'A new entry type',
        'handle' => 'a_new_entry_type',
    ], $overrides);
}

it('can save an entry type', function () {
    expect(EntryType::count())->toBe(1);

    post(action([EntryTypesController::class, 'store']), validEntryTypeData())
        ->assertSessionDoesntHaveErrors()
        ->assertRedirectBack();

    expect(EntryType::count())->toBe(2);
    /** @var \CraftCms\Cms\Entry\Data\EntryType $entryType */
    $entryType = $this->entryTypes->getEntryTypeByHandle('a_new_entry_type');
    expect($entryType->name)->toBe('A new entry type');
    expect($entryType->handle)->toBe('a_new_entry_type');
});

test('values are validated', function (string $attribute, string $value = '') {
    post(action([EntryTypesController::class, 'store']), validEntryTypeData([
        $attribute => $value,
    ]))->assertSessionHasErrors($attribute);
})->with([
    ['name'],
    ['handle'],

    ['id', 'not-a-number'],
    ['name', Str::repeat('a', 256)],

    // Reserved handles are invalid
    ['handle', 'id'],
    ['handle', 'dateCreated'],
    ['handle', 'dateUpdated'],
    ['handle', 'uid'],
    ['handle', 'title'],
    ['handle', Str::repeat('a', 256)],

    // Enum validations
    ['color', 'foo'],
    ['titleTranslationMethod', 'foo'],
    ['slugTranslationMethod', 'foo'],
]);

test('handle needs to be unique', function () {
    post(action([EntryTypesController::class, 'store']), validEntryTypeData())
        ->assertSessionHasNoErrors();

    post(action([EntryTypesController::class, 'store']), validEntryTypeData())
        ->assertSessionHasErrors('handle');
});

test('handle needs to be unique without trashed', function () {
    post(action([EntryTypesController::class, 'store']), validEntryTypeData())
        ->assertSessionHasNoErrors();

    EntryType::latest('id')->first()->update(['dateDeleted' => now()]);

    post(action([EntryTypesController::class, 'store']), validEntryTypeData())
        ->assertSessionHasNoErrors();
});

it('can delete an entry type', function () {
    $newEntryType = EntryType::factory()->create();

    ProjectConfig::rebuild();

    expect(EntryType::count())->toBe(2);

    postJson(action([EntryTypesController::class, 'destroy']), [
        'id' => $newEntryType->id,
    ])->assertOk();

    expect(EntryType::count())->toBe(1);
});

it('can get table data', function () {
    getJson(action([EntryTypesController::class, 'tableData']))
        ->assertOk();
});

it('can render override settings', function () {
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']), [
        'id' => EntryType::first()->id,
    ])->assertOk();
});

it('can apply override settings', function () {
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']), [
        'id' => EntryType::first()->id,
        'settings' => http_build_query([
            'namespace' => [
                'name' => 'Overridden name',
            ],
        ]),
        'settingsNamespace' => 'namespace',
    ])->assertOk()
        ->assertSee('Overridden name');
});
