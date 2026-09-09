<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withSession;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->entryTypes = app(EntryTypes::class);

    EntryType::factory()->create();
});

it('requires authentication', function () {
    Auth::logout();

    get(action([EntryTypesController::class, 'index']))->assertRedirect();
    get(action([EntryTypesController::class, 'create']))->assertRedirect();
    get(action([EntryTypesController::class, 'edit'], [EntryType::first()->id]))->assertRedirect();
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'renderForm']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'store']))->assertUnauthorized();
    deleteJson(action([EntryTypesController::class, 'destroy'], [EntryType::first()->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([EntryTypesController::class, 'edit'], [EntryType::first()->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/entry-types/Edit')
            ->where('readOnly', true));

    // Not allowed
    get(action([EntryTypesController::class, 'create']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'renderForm']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'store']))->assertForbidden();
    deleteJson(action([EntryTypesController::class, 'destroy'], [EntryType::first()->id]))->assertForbidden();
});

test('index can be loaded', function () {
    get(action([EntryTypesController::class, 'index']))
        ->assertOk();
});

test('create can be loaded', function () {
    get(action([EntryTypesController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new entry type'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/entry-types/Edit')
            ->where('form.values.entryTypeId', null)
            ->where('form.values.name', '')
            ->where('form.refreshable', true)
            ->where('submit.method', 'post')
            ->where('refreshUrl', action([EntryTypesController::class, 'renderForm']))
            ->where('form.nodes', function ($nodes): bool {
                $designer = collect($nodes)->first(
                    fn (array $node): bool => ($node['control']['path'] ?? null) === ['fieldLayout'],
                );

                return data_get($designer, 'control.props.withGeneratedFields') === true
                    && data_get($designer, 'control.props.withCardViewDesigner') === true;
            }));
});

it('refreshes fields that depend on the entry type settings', function () {
    Site::factory()->create();
    app(Sites::class)->refreshSites();

    $values = [
        'entryTypeId' => null,
        'name' => '',
        'handle' => '',
        'description' => '',
        'icon' => '',
        'color' => '',
        'uiLabelFormat' => '{title}',
        'titleTranslationMethod' => 'custom',
        'titleTranslationKeyFormat' => '',
        'titleFormat' => '',
        'allowLineBreaksInTitles' => false,
        'showSlugField' => false,
        'slugTranslationMethod' => 'custom',
        'slugTranslationKeyFormat' => '',
        'showStatusField' => true,
        'fieldLayout' => [],
    ];

    $response = postJson(action([EntryTypesController::class, 'renderForm']), [
        'values' => $values,
        'scope' => [],
    ])->assertOk();
    $paths = collect(flattenFormNodes($response->json('form.nodes')))->pluck('control.path')->filter()->values();

    expect($paths)
        ->toContain(['titleTranslationKeyFormat'])
        ->not->toContain(['slugTranslationMethod']);

    $response = postJson(action([EntryTypesController::class, 'renderForm']), [
        'values' => [...$values, 'showSlugField' => true],
        'scope' => [],
    ])->assertOk();
    $paths = collect(flattenFormNodes($response->json('form.nodes')))->pluck('control.path')->filter()->values();

    expect($paths)
        ->toContain(['slugTranslationMethod'])
        ->toContain(['slugTranslationKeyFormat']);
});

it('refreshes a single-site form without translation controls', function () {
    postJson(action([EntryTypesController::class, 'renderForm']), [
        'values' => [
            'allowLineBreaksInTitles' => false,
            'showSlugField' => false,
            'showStatusField' => true,
            'fieldLayout' => [],
        ],
        'scope' => [],
    ])->assertOk();
});

test('create returns the Inertia page for a slideout', function () {
    get(action([EntryTypesController::class, 'create']), [
        'Accept' => 'application/json',
        'X-Craft-Container-Id' => 'entry-type-slideout',
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('inertiaPage', 'settings/entry-types/Edit')
        ->assertJsonPath('inertiaProps.brandNew', true)
        ->assertJsonPath('formAttributes.action', Url::cpUrl('settings/entry-types'));
});

test('it can edit an entry type', function () {
    $entryType = $this->entryTypes->getEntryTypeById(EntryType::first()->id);

    get(action([EntryTypesController::class, 'edit'], [$entryType->id]))
        ->assertOk()
        ->assertSee($entryType->name);
});

test('it ignores stale flashed field layouts when editing an entry type', function () {
    $entryType = $this->entryTypes->getEntryTypeById(EntryType::first()->id);

    withSession(['oldFieldLayout' => []]);

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

    post(action([EntryTypesController::class, 'store']), validEntryTypeData([
        'fieldLayout' => [
            'generatedFields' => [[
                'name' => 'Summary',
                'handle' => 'summary',
                'template' => '{title}',
            ]],
        ],
    ]))
        ->assertSessionDoesntHaveErrors()
        ->assertRedirectBack();

    expect(EntryType::count())->toBe(2);
    /** @var CraftCms\Cms\Entry\Data\EntryType $entryType */
    $entryType = $this->entryTypes->getEntryTypeByHandle('a_new_entry_type');
    expect($entryType->name)->toBe('A new entry type');
    expect($entryType->handle)->toBe('a_new_entry_type');
    expect($entryType->getFieldLayout()->getGeneratedFields()[0])
        ->toMatchArray(['name' => 'Summary', 'handle' => 'summary', 'template' => '{title}']);
});

test('values are validated', function (string $attribute, string $value = '') {
    post(action([EntryTypesController::class, 'store']), validEntryTypeData([
        $attribute => $value,
    ]))->assertSessionHasErrors($attribute);
})->with([
    ['name'],
    ['handle'],

    ['name', Str::repeat('a', 256)],

    // Reserved handles are invalid
    ['handle', 'id'],
    ['handle', 'dateCreated'],
    ['handle', 'dateUpdated'],
    ['handle', 'uid'],
    ['handle', 'title'],
    ['handle', Str::repeat('a', 256)],
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

    deleteJson(action([EntryTypesController::class, 'destroy'], [$newEntryType->id]))->assertOk();

    expect(EntryType::count())->toBe(1);
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
