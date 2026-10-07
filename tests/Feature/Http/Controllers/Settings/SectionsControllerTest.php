<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Form\FormResolver;
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
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\delete;
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
    get(action([SectionsController::class, 'create']))->assertRedirect();
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertRedirect();
    postJson(action([SectionsController::class, 'renderForm']))->assertUnauthorized();
    postJson(action([SectionsController::class, 'store']))->assertUnauthorized();
    deleteJson(action([SectionsController::class, 'destroy'], [Section::first()->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));

    // Not allowed
    get(action([SectionsController::class, 'create']))->assertForbidden();
    postJson(action([SectionsController::class, 'renderForm']))->assertForbidden();
    postJson(action([SectionsController::class, 'store']))->assertForbidden();
    deleteJson(action([SectionsController::class, 'destroy'], [Section::first()->id]))->assertForbidden();
});

test('index can be loaded', function () {
    get(action([SectionsController::class, 'index']))
        ->assertOk();
});

test('index can be sorted', function () {
    Section::factory()->create(['name' => 'zzz Last Section']);
    Section::factory()->create(['name' => 'aaa First Section']);

    get(action([SectionsController::class, 'index'], [
        'sort' => [
            ['field' => 'name', 'direction' => 'asc'],
        ],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('data', 3)
            ->where('data.0.name', 'aaa First Section')
            ->where('data.2.name', 'zzz Last Section')
        );
});

test('create can be loaded', function () {
    get(action([SectionsController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sections/Edit')
            ->where('title', t('Create a new section'))
            ->where('form.values.sectionId', null)
            ->where('form.values.type', SectionType::Channel->value)
            ->where('form.refreshable', true)
            ->where('submit.url', action([SectionsController::class, 'store']))
            ->where('refreshUrl', action([SectionsController::class, 'renderForm']))
            ->where('form.nodes', function ($nodes): bool {
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
            ->where('form.values.sectionId', $section->id)
            ->where('form.values.name', $section->name)
            ->where('form.values.handle', $section->handle));
});

function sectionFormValues(array $overrides = []): array
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
    $paths = fn (array $nodes) => collect(flattenFormNodes($nodes))->pluck('control.path')->filter()->values();

    $channel = postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(),
        'scope' => [],
    ])->assertOk();
    $structure = postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(['type' => SectionType::Structure->value]),
        'scope' => [],
    ])->assertOk();
    $single = postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(['type' => SectionType::Single->value]),
        'scope' => [],
    ])->assertOk();

    expect($paths($channel->json('form.nodes')))
        ->toContain(['propagationMethod'], ['minAuthors'], ['maxAuthors'])
        ->not->toContain(['maxLevels'], ['defaultPlacement'])
        ->and($paths($structure->json('form.nodes')))
        ->toContain(['propagationMethod'], ['maxLevels'], ['defaultPlacement'], ['minAuthors'], ['maxAuthors'])
        ->and($paths($single->json('form.nodes')))
        ->not->toContain(['propagationMethod'], ['maxLevels'], ['defaultPlacement'], ['minAuthors'], ['maxAuthors']);
});

function sectionFormControl(array $nodes, string $path): array
{
    return collect(flattenFormNodes($nodes))->first(fn (array $node) => ($node['control']['path'] ?? null) === [$path])['control'];
}

it('shows the site settings columns for the section type', function () {
    $columns = fn (string $type) => sectionFormControl(postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(['type' => $type]),
        'scope' => [],
    ])->assertOk()->json('form.nodes'), 'sites')['props']['columns'];

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

it('lets each site be enabled in multi-site installs', function () {
    Site::factory()->create();
    app(SitesService::class)->refreshSites();

    $enabled = sectionFormControl(postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(),
        'scope' => [],
    ])->assertOk()->json('form.nodes'), 'sites')['props']['columns']['enabled'];

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
        app(FormResolver::class),
        brandNew: false,
        readOnly: false,
        headlessMode: false,
    )->form()->values['sites'];

    expect(collect($values)->first())
        ->toMatchArray(['singleHomepage' => true, 'singleUri' => '']);
});

it('defaults new preview targets to auto-refresh', function () {
    $previewTargets = sectionFormControl(postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(),
        'scope' => [],
    ])->assertOk()->json('form.nodes'), 'previewTargets');

    expect($previewTargets['props']['defaultValues']['refresh'])->toBeTrue()
        ->and($previewTargets['props']['allowAdd'])->toBeTrue()
        ->and($previewTargets['props']['addRowLabel'])->toBe(t('Add a target'));
});

it('rejects an invalid section type when refreshing', function () {
    postJson(action([SectionsController::class, 'renderForm']), [
        'values' => sectionFormValues(['type' => 'invalid']),
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
            ->where('form.values.sites.'.$site->handle.'.routeType', 'route')
            ->where('form.values.sites.'.$site->handle.'.route', $expected));
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
            ->where('form.values.sites.'.$site->handle.'.routeType', 'template')
            ->where('form.values.sites.'.$site->handle.'.route', 'entries/show'));
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

    delete(action([SectionsController::class, 'destroy'], [$newSection->id]))
        ->assertRedirectBack();

    assertSoftDeleted(Section::class, ['id' => $newSection->id]);
    expect(ProjectConfig::get(ProjectConfigPaths::PATH_SECTIONS.'.'.$newSection->uid))->toBeNull();
    expect(Section::count())->toBe(1);
});
