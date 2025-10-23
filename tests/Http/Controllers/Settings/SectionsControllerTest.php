<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
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
    get(action([SectionsController::class, 'edit'], [Section::first()->id]))->assertSee(t('Changes to these settings aren’t permitted in this environment.'));

    // Not allowed
    get(action([SectionsController::class, 'create']))->assertForbidden();
    postJson(action([SectionsController::class, 'store']))->assertForbidden();
    postJson(action([SectionsController::class, 'destroy']))->assertForbidden();
});

test('index can be loaded', function () {
    get(action([SectionsController::class, 'index']))
        ->assertOk();
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

    post(action([SectionsController::class, 'store']), validEntryTypeData())
        ->assertSessionDoesntHaveErrors()
        ->assertRedirectBack();

    expect(Section::count())->toBe(2);
    tap($this->sections->getSectionByHandle('a_new_section'), function (SectionData $section) {
        expect($section->name)->toBe('A new section');
        expect($section->type)->toBe(SectionType::Single);
        expect(Arr::first($section->getSiteSettings()))->toBeInstanceOf(SectionSiteSettingsData::class);
        expect(Arr::first($section->getSiteSettings())->template)->toBe('_foo');
    });
});

test('values are validated', function (string $attribute, string $value = '') {
    post(action([SectionsController::class, 'store']), validEntryTypeData([
        $attribute => $value,
    ]))->assertSessionHasErrors($attribute);
})->with([
    ['name'],
    ['handle'],
    ['type'],
    ['entryTypes'],
    ['sites'],

    // Reserved handles are invalid
    ['handle', 'id'],
    ['handle', 'dateCreated'],
    ['handle', 'dateUpdated'],
    ['handle', 'uid'],
    ['handle', 'title'],
    ['handle', Str::repeat('a', 256)],

    // Enum validations
    ['type', 'foo'],
    ['defaultPlacement', 'foo'],
    ['propagationMethod', 'foo'],
]);

it('can delete a section', function () {
    $newSection = Section::factory()->create();

    ProjectConfig::rebuild();

    expect(Section::count())->toBe(2);

    postJson(action([SectionsController::class, 'destroy']), [
        'id' => $newSection->id,
    ])->assertOk();

    expect(Section::count())->toBe(1);
});

it('can get table data', function () {
    getJson(action([SectionsController::class, 'tableData']))
        ->assertOk();
});
