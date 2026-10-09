<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\ViewModels\SectionEditViewModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites as SitesService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->sections = app(Sections::class);

    Section::factory()->create(['name' => 'mmm Middle Section']);
});

it('requires authentication', function () {
    Auth::logout();

    get(action([SectionsController::class, 'index']))->assertRedirect();
    postJson(action([SectionsController::class, 'tableData']))->assertUnauthorized();
    get(action([SectionsController::class, 'create']))->assertRedirect();
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertRedirect();
    postJson(action([SectionsController::class, 'renderUi']))->assertUnauthorized();
    postJson(action([SectionsController::class, 'store']))->assertUnauthorized();
    deleteJson(action([SectionsController::class, 'destroy'], [Section::first()->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));

    // Not allowed
    get(action([SectionsController::class, 'create']))->assertForbidden();
    postJson(action([SectionsController::class, 'renderUi']))->assertForbidden();
    postJson(action([SectionsController::class, 'store']))->assertForbidden();
    deleteJson(action([SectionsController::class, 'destroy'], [Section::first()->id]))->assertForbidden();
});

test('index serves linked section rows and actions allowed by admin changes', function (bool $allowAdminChanges) {
    Cms::config()->allowAdminChanges = $allowAdminChanges;
    $section = Section::firstOrFail();
    $response = get(action([SectionsController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Ui'));
    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))
        ->firstWhere('component', 'craft:admin-table');

    expect($table['props']['dataUrl'])->toBe(action([SectionsController::class, 'tableData']))
        ->and($table['props']['createUrl'])->toBe($allowAdminChanges ? action([SectionsController::class, 'create']) : null)
        ->and($table['props']['deletable'])->toBe($allowAdminChanges);

    $response = postJson($table['props']['dataUrl'])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('pagination.total', 1);
    $row = $response->json('data.0');
    $handle = new Crawler($row['handle']['html'])->filter('craft-copy-attribute');

    expect($row)->toMatchArray([
        'id' => $section->id,
        'name' => ['label' => 'mmm Middle Section', 'url' => action([SectionsController::class, 'edit'], $section)],
        'type' => 'Channel',
    ])
        ->and($handle->attr('value'))->toBe($section->handle)
        ->and($handle->text())->toBe($section->handle);

    if ($allowAdminChanges) {
        expect($row['_deleteUrl'])->toBe(action([SectionsController::class, 'destroy'], $section))
            ->and($row['_deleteConfirmMessage'])->toBe('Are you sure you want to delete “mmm Middle Section” and all its entries?');
    } else {
        expect($row)->not->toHaveKey('_deleteUrl');
    }
})->with(['writable' => true, 'read-only' => false]);

it('requires admin access for the section index and table data', function () {
    $user = UserModel::firstOrFail();
    $user->update(['admin' => false]);
    UserPermissions::saveUserPermissions($user->id, ['accessCp']);
    actingAs(User::findOne($user->id));

    get(action([SectionsController::class, 'index']))->assertForbidden();
    postJson(action([SectionsController::class, 'tableData']))->assertForbidden();
});

test('table data filters sorts and paginates sections', function (string $field, string $direction, string $name) {
    Section::firstOrFail()->update(['handle' => 'm_middle']);
    Section::factory()->create(['name' => 'zzz Last Section', 'handle' => 'a_last', 'type' => SectionType::Single]);
    Section::factory()->create(['name' => 'aaa First Section', 'handle' => 'z_first', 'type' => SectionType::Structure]);
    Section::factory()->create(['name' => 'Ignored record', 'handle' => 'ignored_record']);
    Cms::config()->pageTrigger = 'custom-page';

    postJson(action([SectionsController::class, 'tableData']), [
        'search' => 'Section',
        'sort' => [['field' => $field, 'direction' => $direction]],
        'per_page' => 2,
        'page' => 2,
    ])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name.label', $name)
        ->assertJsonPath('pagination.total', 3)
        ->assertJsonPath('pagination.per_page', 2)
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.last_page', 2)
        ->assertJsonPath('pagination.from', 3)
        ->assertJsonPath('pagination.to', 3);
})->with([
    'ascending names' => ['name', 'asc', 'zzz Last Section'],
    'descending names' => ['name', 'desc', 'aaa First Section'],
    'ascending handles' => ['handle', 'asc', 'aaa First Section'],
    'descending handles' => ['handle', 'desc', 'zzz Last Section'],
    'ascending types' => ['type', 'asc', 'aaa First Section'],
    'descending types' => ['type', 'desc', 'mmm Middle Section'],
]);

test('create can be loaded', function () {
    get(action([SectionsController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sections/Edit')
            ->where('title', t('Create a new section'))
            ->where('ui.values.sectionId', null)
            ->where('ui.values.type', SectionType::Channel->value)
            ->where('ui.refreshable', true)
            ->where('submit.url', action([SectionsController::class, 'store']))
            ->where('refreshUrl', action([SectionsController::class, 'renderUi']))
            ->where('ui.nodes', function ($nodes): bool {
                $paths = collect($nodes)->pluck('control.path')->filter();

                return $paths->contains(['entryTypes'])
                    && $paths->contains(['workflowId'])
                    && $paths->contains(['sites'])
                    && $paths->contains(['previewTargets']);
            }));
});

test('it can edit a section', function () {
    $section = $this->sections->getSectionById(Section::first()->id);

    get(action([SectionsController::class, 'edit'], [$section->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sections/Edit')
            ->where('ui.values.sectionId', $section->id)
            ->where('ui.values.name', $section->name)
            ->where('ui.values.handle', $section->handle));
});

test('the edit slideout links to the section’s edit page', function () {
    $section = $this->sections->getSectionById(Section::first()->id);

    get(action([SectionsController::class, 'edit'], [$section->id]), [
        'Accept' => 'application/json',
        'X-Craft-Container-Id' => 'section-slideout',
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('editUrl', $section->getCpEditUrl());
});

function sectionUiValues(array $overrides = []): array
{
    return array_merge([
        'sectionId' => null,
        'name' => 'News',
        'handle' => 'news',
        'type' => SectionType::Channel->value,
        'entryTypes' => [],
        'enableVersioning' => true,
        'minAuthors' => 0,
        'maxAuthors' => '',
        'maxLevels' => '',
        'propagationMethod' => 'all',
        'defaultPlacement' => 'end',
        'previewTargets' => [],
        'sites' => ['default' => ['enabled' => true]],
    ], $overrides);
}

it('refreshes the fields that depend on the section type', function () {
    Site::factory()->create();
    app(SitesService::class)->refreshSites();
    $paths = fn (array $nodes) => collect(flattenUiNodes($nodes))->pluck('control.path')->filter()->values();

    $channel = postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(),
        'scope' => [],
    ])->assertOk();
    $structure = postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(['type' => SectionType::Structure->value]),
        'scope' => [],
    ])->assertOk();
    $single = postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(['type' => SectionType::Single->value]),
        'scope' => [],
    ])->assertOk();

    expect($paths($channel->json('ui.nodes')))
        ->toContain(['propagationMethod'], ['minAuthors'], ['maxAuthors'])
        ->not->toContain(['maxLevels'], ['defaultPlacement'])
        ->and($paths($structure->json('ui.nodes')))
        ->toContain(['propagationMethod'], ['maxLevels'], ['defaultPlacement'], ['minAuthors'], ['maxAuthors'])
        ->and($paths($single->json('ui.nodes')))
        ->not->toContain(['propagationMethod'], ['maxLevels'], ['defaultPlacement'], ['minAuthors'], ['maxAuthors']);
});

function sectionUiControl(array $nodes, string $path): array
{
    return collect(flattenUiNodes($nodes))->first(fn (array $node) => ($node['control']['path'] ?? null) === [$path])['control'];
}

it('shows the site settings columns for the section type', function () {
    $columns = fn (string $type) => sectionUiControl(postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(['type' => $type]),
        'scope' => [],
    ])->assertOk()->json('ui.nodes'), 'sites')['props']['columns'];

    $channel = $columns(SectionType::Channel->value);
    $single = $columns(SectionType::Single->value);

    expect($channel['uriFormat']['type'])->toBe('singleline')
        ->and($channel['enabledByDefault']['type'])->toBe('lightswitch')
        ->and($channel['singleHomepage']['type'])->toBe('hidden')
        ->and($channel['singleUri']['type'])->toBe('hidden')
        ->and($single['singleHomepage']['type'])->toBe('checkbox')
        ->and($single['singleHomepage']['toggle'])->toBe(['!singleUri'])
        ->and($single['singleUri']['type'])->toBe('singleline')
        ->and($single['uriFormat']['type'])->toBe('hidden')
        ->and($single['enabledByDefault']['type'])->toBe('hidden')
        // Single-site installs have nothing to enable or disable.
        ->and($channel['enabled']['type'])->toBe('hidden')
        ->and($channel['route']['type'])->toBe('template')
        ->and($channel['route']['options'][0]['type'])->toBe('optgroup');
});

it('keeps the table rows when refreshing', function () {
    $ui = postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues([
            'previewTargets' => [['label' => 'Page', 'urlFormat' => '{url}', 'refresh' => true]],
        ]),
        'scope' => [],
    ])->assertOk()->json('ui.nodes');

    // Saving submits only the values of controls in these row UIs.
    expect(sectionUiControl($ui, 'sites')['uis'][0]['scope'])->toBe(['sites', 'default'])
        ->and(sectionUiControl($ui, 'previewTargets')['uis'][0]['scope'])->toBe(['previewTargets', '0']);
});

it('lets each site be enabled in multi-site installs', function () {
    Site::factory()->create();
    app(SitesService::class)->refreshSites();

    $enabled = sectionUiControl(postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(),
        'scope' => [],
    ])->assertOk()->json('ui.nodes'), 'sites')['props']['columns']['enabled'];

    expect($enabled['type'])->toBe('lightswitch')
        ->and($enabled['toggle'])->toContain('uriFormat', 'route', 'enabledByDefault');
});

it('checks the homepage box for a single saved at the homepage URI', function () {
    $section = $this->sections->getSectionById(Section::first()->id);
    $section->type = SectionType::Single;
    foreach ($section->getSiteSettings() as $siteSettings) {
        $siteSettings->uriFormat = Element::HOMEPAGE_URI;
    }

    $values = new SectionEditViewModel(
        $section,
        app(SitesService::class),
        app(UiResolver::class),
        brandNew: false,
        readOnly: false,
        headlessMode: false,
    )->ui()->values['sites'];

    expect(collect($values)->first())
        ->toMatchArray(['singleHomepage' => true, 'singleUri' => '']);
});

it('defaults new preview targets to auto-refresh', function () {
    $previewTargets = sectionUiControl(postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(),
        'scope' => [],
    ])->assertOk()->json('ui.nodes'), 'previewTargets');

    expect($previewTargets['props']['defaultValues']['refresh'])->toBeTrue()
        ->and($previewTargets['props']['allowAdd'])->toBeTrue()
        ->and($previewTargets['props']['addRowLabel'])->toBe(t('Add a target'));
});

it('rejects an invalid section type when refreshing', function () {
    postJson(action([SectionsController::class, 'renderUi']), [
        'values' => sectionUiValues(['type' => 'invalid']),
        'scope' => [],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('values.type');
});

it('404s when a section does not exist', function () {
    get(action([SectionsController::class, 'edit'], [999]))
        ->assertNotFound();
});

function validSectionData(array $overrides = []): array
{
    $entryType = EntryType::factory()->create();

    return array_merge([
        'name' => 'A new section',
        'handle' => 'a_new_section',
        'type' => SectionType::Single->value,
        'entryTypes' => [
            $entryType->id,
        ],
        'sites' => [
            Site::first()->handle => [
                'enabled' => true,
                'singleHomepage' => true,
                'template' => '_foo',
            ],
        ],
    ], $overrides);
}

it('saves and reloads normalized route destinations without requiring application code to exist', function (string $input, string $expected) {
    $site = Site::first();
    $data = validSectionData();
    $data['sites'][$site->handle]['routeType'] = 'route';
    unset($data['sites'][$site->handle]['template']);
    $data['sites'][$site->handle]['route'] = $input;

    postJson(action([SectionsController::class, 'store']), $data)->assertOk();

    $section = Section::where('handle', 'a_new_section')->firstOrFail();
    assertDatabaseHas('sections_sites', ['sectionId' => $section->id, 'siteId' => $site->id, 'template' => null, 'route' => $expected]);
    expect(ProjectConfig::get(ProjectConfigPaths::PATH_SECTIONS.'.'.$section->uid.'.siteSettings.'.$site->uid.'.route'))->toBe($expected);
    get(action([SectionsController::class, 'edit'], [$section->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.values.sites.'.$site->handle.'.routeType', 'route')
            ->where('ui.values.sites.'.$site->handle.'.route', $expected));
})->with([
    'named route' => [' services.show ', 'services.show'],
    'invokable' => ['App\\Http\\Controllers\\ServiceController', 'App\\Http\\Controllers\\ServiceController'],
    'leading slash and class token' => [' \\App\\Http\\Controllers\\ServiceController::class ', 'App\\Http\\Controllers\\ServiceController'],
    'at method' => ['App\\Http\\Controllers\\ServiceController@show', 'App\\Http\\Controllers\\ServiceController@show'],
    'colon method' => ['App\\Http\\Controllers\\ServiceController::show', 'App\\Http\\Controllers\\ServiceController@show'],
    'class token and at method' => ['App\\Http\\Controllers\\ServiceController::class@show', 'App\\Http\\Controllers\\ServiceController@show'],
    'class token and colon method' => ['App\\Http\\Controllers\\ServiceController::class::show', 'App\\Http\\Controllers\\ServiceController@show'],
]);

it('clears a section route when switching its destination to a template', function () {
    $site = Site::firstOrFail();
    $data = validSectionData();
    unset($data['sites'][$site->handle]['template']);
    $data['sites'][$site->handle]['routeType'] = 'route';
    $data['sites'][$site->handle]['route'] = 'entries.show';
    postJson(action([SectionsController::class, 'store']), $data)->assertOk();

    $section = Section::where('handle', 'a_new_section')->firstOrFail();
    $data['sectionId'] = $section->id;
    $data['sites'][$site->handle]['routeType'] = 'template';
    $data['sites'][$site->handle]['route'] = 'entries/show';
    postJson(action([SectionsController::class, 'store']), $data)->assertOk();

    assertDatabaseHas('sections_sites', ['sectionId' => $section->id, 'siteId' => $site->id, 'template' => 'entries/show', 'route' => null]);
    get(action([SectionsController::class, 'edit'], [$section->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.values.sites.'.$site->handle.'.routeType', 'template')
            ->where('ui.values.sites.'.$site->handle.'.route', 'entries/show'));
});

it('rejects malformed route syntax when saving a section', function () {
    $data = validSectionData();
    $data['sites'][Site::first()->handle]['template'] = null;
    $data['sites'][Site::first()->handle]['route'] = 'App\\Controller@show()';

    postJson(action([SectionsController::class, 'store']), $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sites');
});

it('can save a section', function () {
    expect(Section::count())->toBe(1);
    $workflow = Workflow::query()->create([
        'name' => 'Editorial workflow',
        'uid' => Str::uuid7()->toString(),
    ]);

    post(action([SectionsController::class, 'store']), validSectionData([
        'workflowId' => $workflow->id,
    ]))
        ->assertSessionDoesntHaveErrors()
        ->assertRedirectBack();

    expect(Section::count())->toBe(2);
    /** @var Section $section */
    $section = $this->sections->getSectionByHandle('a_new_section');
    expect($section->name)->toBe('A new section');
    expect($section->type)->toBe(SectionType::Single);
    expect($section->workflowId)->toBe($workflow->id);
    expect(ProjectConfig::get(ProjectConfigPaths::PATH_SECTIONS.'.'.$section->uid.'.workflow'))->toBe($workflow->uid);
    expect(Arr::first($section->getSiteSettings()))->toBeInstanceOf(SectionSiteSettingsData::class);
    expect(Arr::first($section->getSiteSettings())->template)->toBe('_foo');
});

test('values are validated', function (string $attribute, string $value = '', ?string $errorAttribute = null) {
    post(action([SectionsController::class, 'store']), validSectionData([
        $attribute => $value,
    ]))->assertSessionHasErrors($errorAttribute ?? $attribute);
})->with([
    ['name'],
    ['handle'],
    ['entryTypes'],
    ['sites', '', 'siteSettings'],

    // Reserved handles are invalid
    ['handle', 'id'],
    ['handle', 'dateCreated'],
    ['handle', 'dateUpdated'],
    ['handle', 'uid'],
    ['handle', 'title'],
    ['handle', Str::repeat('a', 256)],
]);

test('handle needs to be unique', function () {
    $data = validSectionData();

    post(action([SectionsController::class, 'store']), $data)
        ->assertSessionHasNoErrors();

    post(action([SectionsController::class, 'store']), $data)
        ->assertSessionHasErrors('handle');
});

test('handle needs to be unique without trashed', function () {
    $data = validSectionData();

    post(action([SectionsController::class, 'store']), $data)
        ->assertSessionHasNoErrors();

    CraftCms\Cms\Support\Facades\Sections::deleteSectionById(Section::latest('id')->first()->id);

    post(action([SectionsController::class, 'store']), $data)
        ->assertSessionHasNoErrors();
});

it('can delete a section', function () {
    $newSection = Section::factory()->create();
    assertDatabaseHas(Section::class, ['id' => $newSection->id]);

    ProjectConfig::rebuild();

    expect(Section::count())->toBe(2);

    deleteJson(action([SectionsController::class, 'destroy'], [$newSection->id]))
        ->assertOk()
        ->assertJsonPath('message', 'Section “'.$newSection->name.'” deleted.');

    assertSoftDeleted(Section::class, ['id' => $newSection->id]);
    expect(ProjectConfig::get(ProjectConfigPaths::PATH_SECTIONS.'.'.$newSection->uid))->toBeNull();
    expect(Section::count())->toBe(1);
});
