<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->sections = app(Sections::class);

    Section::factory()->create();
});

it('requires authentication', function () {
    Auth::logout();

    get(action([SectionsController::class, 'index']))->assertRedirect();
    get(action([SectionsController::class, 'create']))->assertRedirect();
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertRedirect();
    get(action([SectionsController::class, 'tableData']))->assertRedirect();
    postJson(action([SectionsController::class, 'store']))->assertUnauthorized();
    postJson(action([SectionsController::class, 'destroy']))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));

    // Not allowed
    get(action([SectionsController::class, 'create']))->assertForbidden();
    postJson(action([SectionsController::class, 'store']))->assertForbidden();
    postJson(action([SectionsController::class, 'destroy']))->assertForbidden();
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
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->has('data', 3)
            ->where('data.0.name', 'aaa First Section')
            ->where('data.2.name', 'zzz Last Section')
        );
});

test('create can be loaded', function () {
    get(action([SectionsController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new section'));
});

test('it can edit a section', function () {
    $section = $this->sections->getSectionById(Section::first()->id);

    get(action([SectionsController::class, 'edit'], [$section->id]))
        ->assertOk()
        ->assertSee($section->name);
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

it('can save a section', function () {
    expect(Section::count())->toBe(1);

    post(action([SectionsController::class, 'store']), validSectionData())
        ->assertSessionDoesntHaveErrors()
        ->assertRedirectBack();

    expect(Section::count())->toBe(2);
    /** @var Section $section */
    $section = $this->sections->getSectionByHandle('a_new_section');
    expect($section->name)->toBe('A new section');
    expect($section->type)->toBe(SectionType::Single);
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

    Section::latest('id')->first()->update(['dateDeleted' => now()]);

    post(action([SectionsController::class, 'store']), $data)
        ->assertSessionHasNoErrors();
});

it('can delete a section', function () {
    $newSection = Section::factory()->create();
    assertDatabaseHas(Section::class, ['id' => $newSection->id]);

    ProjectConfig::rebuild();

    expect(Section::count())->toBe(2);

    postJson(action([SectionsController::class, 'destroy']), [
        'id' => $newSection->id,
    ])->assertRedirectBack();

    assertSoftDeleted(Section::class, ['id' => $newSection->id]);
    expect(ProjectConfig::get(ProjectConfigPaths::PATH_SECTIONS.'.'.$newSection->uid))->toBeNull();
    expect(Section::count())->toBe(1);
});

it('can get table data', function () {
    getJson(action([SectionsController::class, 'tableData']))
        ->assertOk();
});
