<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

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
    postJson(action([EntryTypesController::class, 'renderSelect']))->assertUnauthorized();
    postJson(action([EntryTypesController::class, 'renderUi']))->assertUnauthorized();
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
    postJson(action([EntryTypesController::class, 'renderSelect']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'renderUi']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'applyOverrideSettings']))->assertForbidden();
    postJson(action([EntryTypesController::class, 'store']))->assertForbidden();
    deleteJson(action([EntryTypesController::class, 'destroy'], [EntryType::first()->id]))->assertForbidden();
});

test('index serves chips, copyable handles, usages, and permitted actions', function (bool $allowAdminChanges, ?int $perPage) {
    Cms::config()->allowAdminChanges = $allowAdminChanges;
    $entryType = EntryType::firstOrFail();
    $entryType->update([
        'name' => 'News & Updates',
        'handle' => 'newsUpdates',
        'description' => 'Editorial **articles**.',
        'icon' => 'newspaper',
        'color' => Color::Red->value,
    ]);
    $section = Section::factory()->withEntryTypes($entryType)->create(['name' => 'News']);
    $otherSection = Section::factory()->withEntryTypes($entryType)->create(['name' => 'Updates']);
    $response = get(action([EntryTypesController::class, 'index'], $perPage === null ? [] : ['per_page' => $perPage]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')->where('readOnly', ! $allowAdminChanges));
    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))->firstWhere('component', 'craft:admin-table');

    expect($table['props']['createUrl'])->toBe($allowAdminChanges ? action([EntryTypesController::class, 'create']) : null)
        ->and($table['props']['deletable'])->toBe($allowAdminChanges);

    expect($table['props']['rows'])->toHaveCount(1)
        ->and($table['props']['pagination']['total'])->toBe(1)
        ->and($table['props']['pagination']['per_page'])->toBe($perPage ?? 100);
    $row = $table['props']['rows'][0];
    $chip = new Crawler($row['name']['html']);
    $handle = new Crawler($row['handle']['html'])->filter('craft-copy-attribute');
    $usages = new Crawler($row['usages']['html']);

    expect($row['id'])->toBe($entryType->id)
        ->and($chip->filter('craft-chip')->attr('class'))->toContain('cp-color-red')
        ->and($chip->filter('craft-icon')->attr('name'))->toBe('newspaper')
        ->and($chip->filter('a')->text())->toBe('News & Updates')
        ->and($chip->filter('a')->attr('href'))->toBe(Url::cpUrl("settings/entry-types/$entryType->id"))
        ->and($chip->filter('craft-info-icon strong')->text())->toBe('articles')
        ->and($handle->attr('value'))->toBe('newsUpdates')
        ->and($handle->text())->toBe('newsUpdates')
        ->and($usages->filter('a')->text())->toBe('News')
        ->and($usages->filter('a')->attr('href'))->toBe(Url::cpUrl("settings/sections/$section->id"))
        ->and($usages->filter('craft-button')->text())->toBe('+1');
    $otherUsages = new Crawler(json_decode($usages->filter('craft-button')->attr('data-other'), true));

    expect($otherUsages->filter('a')->text())->toBe('Updates')
        ->and($otherUsages->filter('a')->attr('href'))->toBe(Url::cpUrl("settings/sections/$otherSection->id"));

    if ($allowAdminChanges) {
        expect($row['_deleteUrl'])->toBe(action([EntryTypesController::class, 'destroy'], $entryType))
            ->and($row['_deleteConfirmMessage'])->toBe('Are you sure you want to delete “News & Updates” and all entries of that type?');
    } else {
        expect($row)->not->toHaveKey('_deleteUrl');
    }
})->with(['writable' => [true, 2], 'read-only' => [false, null]]);

it('requires admin access for the entry type index', function () {
    $user = UserModel::firstOrFail();
    $user->update(['admin' => false]);
    UserPermissions::saveUserPermissions($user->id, ['accessCp']);
    actingAs(User::findOne($user->id));

    get(action([EntryTypesController::class, 'index']))->assertForbidden();
});

it('searches, sorts, and paginates entry type rows', function (string $field, string $direction, string $expectedHandle) {
    EntryType::firstOrFail()->update(['name' => 'Middle Article', 'handle' => 'middleArticle']);
    EntryType::factory()->create(['name' => 'First Article', 'handle' => 'zzzArticle']);
    EntryType::factory()->create(['name' => 'Last Article', 'handle' => 'aaaArticle']);
    EntryType::factory()->create(['name' => 'Ignored', 'handle' => 'ignored']);
    Cms::config()->pageTrigger = 'custom-page';

    $response = get(action([EntryTypesController::class, 'index'], [
        'search' => 'Article',
        'sort' => [['field' => $field, 'direction' => $direction]],
        'custom-page' => 2,
        'per_page' => 2,
    ]), [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'Ui',
        'X-Inertia-Partial-Data' => 'ui',
    ])->assertOk()->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Ui')
        ->assertJsonCount(1, 'props.ui.nodes.0.props.rows')
        ->assertJsonPath('props.ui.nodes.0.props.pagination.total', 3)
        ->assertJsonPath('props.ui.nodes.0.props.pagination.current_page', 2)
        ->assertJsonPath('props.ui.nodes.0.props.pagination.per_page', 2)
        ->assertJsonPath('props.ui.nodes.0.props.pagination.last_page', 2)
        ->assertJsonPath('props.ui.nodes.0.props.pagination.from', 3)
        ->assertJsonPath('props.ui.nodes.0.props.pagination.to', 3);

    expect(new Crawler($response->json('props.ui.nodes.0.props.rows.0.handle.html'))->filter('craft-copy-attribute')->text())->toBe($expectedHandle);
})->with([
    'ascending names' => ['name', 'asc', 'middleArticle'],
    'descending names' => ['name', 'desc', 'zzzArticle'],
    'ascending handles' => ['handle', 'asc', 'zzzArticle'],
    'descending handles' => ['handle', 'desc', 'aaaArticle'],
    'ascending usages retain name ordering' => ['usages', 'asc', 'middleArticle'],
    'descending usages retain name ordering' => ['usages', 'desc', 'zzzArticle'],
]);

it('finds entry types by handle and returns no rows for an unmatched search', function (string $search, bool $matches) {
    EntryType::firstOrFail()->update(['name' => 'Editorial', 'handle' => 'articleContent']);
    EntryType::factory()->create(['name' => 'Other', 'handle' => 'other']);

    $response = get(action([EntryTypesController::class, 'index'], ['search' => $search]))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('ui.nodes.0.props.rows', $matches ? 1 : 0)
        ->where('ui.nodes.0.props.pagination.total', $matches ? 1 : 0)
        ->where('ui.nodes.0.props.pagination.from', $matches ? 1 : null)
        ->where('ui.nodes.0.props.pagination.to', $matches ? 1 : null));

    if ($matches) {
        expect(new Crawler($response->inertiaProps('ui.nodes.0.props.rows.0.name.html'))->filter('a')->text())->toBe('Editorial');
    }
})->with([
    'handle search' => ['articleContent', true],
    'unmatched search' => ['nonexistentEntryType', false],
]);

test('create can be loaded', function () {
    get(action([EntryTypesController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new entry type'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/entry-types/Edit')
            ->where('ui.values.entryTypeId', null)
            ->where('ui.values.name', '')
            ->where('ui.refreshable', true)
            ->where('submit.method', 'post')
            ->where('refreshUrl', action([EntryTypesController::class, 'renderUi']))
            ->where('ui.nodes', function ($nodes): bool {
                $designer = collect($nodes)->first(
                    fn (array $node): bool => ($node['control']['path'] ?? null) === ['fieldLayout'],
                );

                return data_get($designer, 'control.props.withGeneratedFields') === true
                    && data_get($designer, 'control.props.withCardViewDesigner') === true;
            }));
});

it('offers the palette colors for the entry type color', function () {
    get(action([EntryTypesController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes', function ($nodes): bool {
                $color = collect($nodes)->first(
                    fn (array $node): bool => ($node['control']['path'] ?? null) === ['color'],
                );

                return data_get($color, 'control.component') === 'craft:color-select'
                    && data_get($color, 'control.props.allowTransparent') === true
                    && data_get($color, 'control.props.blankLabel') === t('No color')
                    && data_get($color, 'control.props.colors') === array_column(Color::cases(), 'value');
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
        'showPostDateField' => true,
        'showExpiryDateField' => true,
        'fieldLayout' => [],
    ];

    $response = postJson(action([EntryTypesController::class, 'renderUi']), [
        'values' => $values,
        'scope' => [],
    ])->assertOk();
    $paths = collect(flattenUiNodes($response->json('ui.nodes')))->pluck('control.path')->filter()->values();

    expect($paths)
        ->toContain(['titleTranslationKeyFormat'])
        ->not->toContain(['slugTranslationMethod']);

    $response = postJson(action([EntryTypesController::class, 'renderUi']), [
        'values' => [...$values, 'showSlugField' => true],
        'scope' => [],
    ])->assertOk();
    $paths = collect(flattenUiNodes($response->json('ui.nodes')))->pluck('control.path')->filter()->values();

    expect($paths)
        ->toContain(['slugTranslationMethod'])
        ->toContain(['slugTranslationKeyFormat']);
});

it('refreshes a single-site UI without translation controls', function () {
    postJson(action([EntryTypesController::class, 'renderUi']), [
        'values' => [
            'allowLineBreaksInTitles' => false,
            'showSlugField' => false,
            'showStatusField' => true,
            'showPostDateField' => true,
            'showExpiryDateField' => true,
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

    $response = postJson(
        action([EntryTypesController::class, 'store']),
        validEntryTypeData([
            'fieldLayout' => [
                'generatedFields' => [[
                    'name' => 'Summary',
                    'handle' => 'summary',
                    'template' => '{title}',
                ]],
            ],
        ]),
        ['Accept' => 'text/html', 'X-Inertia' => 'true'],
    );

    expect(EntryType::count())->toBe(2);
    /** @var CraftCms\Cms\Entry\Data\EntryType $entryType */
    $entryType = $this->entryTypes->getEntryTypeByHandle('a_new_entry_type');
    expect($entryType->name)->toBe('A new entry type');
    expect($entryType->handle)->toBe('a_new_entry_type');
    expect($entryType->getFieldLayout()->getGeneratedFields()[0])
        ->toMatchArray(['name' => 'Summary', 'handle' => 'summary', 'template' => '{title}']);
    $response->assertRedirect($entryType->getCpEditUrl());
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

it('renders the entry type select', function () {
    $entryType = EntryType::first();
    $other = EntryType::factory()->create([
        'handle' => 'otherType',
        'icon' => 'newspaper',
        'color' => Color::Red->value,
    ]);

    $html = postJson(action([EntryTypesController::class, 'renderSelect']), [
        'value' => [['id' => $entryType->id, 'name' => 'Overridden']],
        'allowOverrides' => true,
        'create' => true,
        'name' => 'entryTypes',
        'disabled' => false,
    ])
        ->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml'])
        ->json('html');

    expect($html)
        ->toContain('<craft-component-select')
        ->toContain('name="entryTypes[]"')
        ->toContain('Overridden')
        ->toContain('command="--create-item"')
        ->toContainTag('craft-component-select', ['checkbox-options' => true])
        ->toContainTag('craft-action-item', ['data-id' => $entryType->id, 'type' => 'checkbox', 'checked' => true])
        ->toContainTag('craft-action-item', ['data-id' => $other->id, 'type' => 'checkbox', 'checked' => false, 'icon' => 'newspaper', 'icon-color' => 'red'])
        ->toContain('>otherType</span>');
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
