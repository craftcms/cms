<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('returns an Inertia response with elements and pagination', function () {
    EntryModel::factory()->count(3)->create();

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Index')
            ->has('data')
            ->has('pagination')
            ->has('sort')
            ->has('sources')
        );
});

it('paginates elements via query params', function () {
    EntryModel::factory()->count(5)->create();

    get("/{$this->cpTrigger}/content/entries?".http_build_query(['per_page' => 2, 'page' => 1]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Index')
            ->where('pagination.per_page', 2)
            ->where('pagination.total', 5)
            ->where('pagination.current_page', 1)
        );
});

it('accepts sort parameters', function () {
    EntryModel::factory()->createElement(['title' => 'Zebra']);
    EntryModel::factory()->createElement(['title' => 'Apple']);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'sort' => [['field' => 'title', 'direction' => 'asc']],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sort.0.field', 'title')
            ->where('sort.0.direction', 'asc')
        );
});

it('defaults to the source’s defaultSort', function () {
    EntryModel::factory()->create();

    // The "All entries" (`*`) source declares `defaultSort: ['postDate', 'desc']`.
    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sort.0.field', 'postDate')
            ->where('sort.0.direction', 'desc')
        );
});

it('clamps per_page to minimum of 1', function () {
    EntryModel::factory()->create();

    get("/{$this->cpTrigger}/content/entries?per_page=0")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.per_page', 1)
        );
});

it('scopes the list to the selected section source', function () {
    $a = Section::factory()->create(['type' => SectionType::Channel]);
    $b = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($a)->create();
    EntryModel::factory()->forSection($b)->count(3)->create();

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$a->uid}",
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('scopes the legacy singles source to single sections only', function () {
    $single = Section::factory()->create(['type' => SectionType::Single]);
    $channel = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($single)->create();
    EntryModel::factory()->forSection($channel)->count(3)->create();

    // The legacy singles alias must not spill the channel's entries into the list.
    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => 'singles',
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('orders a structure source by its structure rather than a literal column', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    $a = EntryModel::factory()->forSection($section)->create();
    $b = EntryModel::factory()->forSection($section)->create();
    $c = EntryModel::factory()->forSection($section)->create();

    foreach ([$c, $a, $b] as $entry) {
        Structures::appendToRoot($structure->id, EntryElement::find()->id($entry->id)->one());
    }

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sort.0.field', 'structure')
            ->where('pagination.total', 3)
            ->where('data.0.id', $c->id)
            ->where('data.1.id', $a->id)
            ->where('data.2.id', $b->id)
        );
});

it('shows disabled entries by default, matching Craft 5 status handling', function () {
    // The index must default to *all* statuses (Craft 5 seeds
    // baseCriteria.status = null on every load), so a disabled entry still
    // shows up — otherwise a disabled Single vanishes from its source.
    $section = Section::factory()->create(['type' => SectionType::Channel]);

    $entry = EntryModel::factory()->forSection($section)->create();
    // Globally disable the element (elements.enabled = false) while leaving the
    // per-site row enabled — the exact shape of a disabled Single.
    $entry->element->update(['enabled' => false]);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('narrows to a specific status when one is requested', function () {
    $section = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($section)->create();
    $disabled = EntryModel::factory()->forSection($section)->create();
    $disabled->element->update(['enabled' => false]);

    // Explicitly asking for enabled entries must exclude the disabled one.
    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'status' => 'enabled',
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('shows a single that was auto-created on section save', function () {
    // Save a Single section through the service, which auto-creates its lone
    // entry via ensureSingleEntry() — the real path a single goes through.
    SectionsFacade::saveSection(new SectionData([
        'name' => 'About',
        'handle' => 'about',
        'type' => SectionType::Single,
        'entryTypes' => [EntryType::factory()->create()->id],
        'siteSettings' => [
            new SectionSiteSettingsData([
                'siteId' => Sites::getCurrentSite()->id,
            ]),
        ],
    ]));

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => 'singles',
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('narrows results with a search term', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->indexed()->createElement(['title' => 'Needle entry']);
    EntryModel::factory()->forSection($section)->indexed()->createElement(['title' => 'Haystack entry']);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'search' => 'Needle',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
            ->where('search', 'Needle')
        );
});

it('clamps out-of-range page numbers to the last page', function () {
    EntryModel::factory()->count(3)->create();

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'per_page' => 2,
        Cms::config()->getPageTriggerParam() => 99,
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.current_page', 2)
            ->where('pagination.last_page', 2)
            ->count('data', 1)
        );
});

it('serializes bulk action items for the active source', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->create();

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('actions')
        );
});

it('selects the source for a section-handle URL', function () {
    $section = Section::factory()->create(['handle' => 'blog']);

    get("/{$this->cpTrigger}/content/entries/blog")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', "section:{$section->uid}")
        );
});

it('selects the source for a single’s own section-handle URL', function () {
    $section = Section::factory()->create([
        'type' => SectionType::Single,
        'handle' => 'homePage',
    ]);

    get("/{$this->cpTrigger}/content/entries/homePage")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', "section:{$section->uid}")
        );
});

it('still resolves the legacy singles URL, listing every single', function () {
    $home = Section::factory()->create(['type' => SectionType::Single]);
    $about = Section::factory()->create(['type' => SectionType::Single]);
    $channel = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($home)->create();
    EntryModel::factory()->forSection($about)->create();
    EntryModel::factory()->forSection($channel)->count(3)->create();

    // `singles` is no longer a real source, but the URL stays bookmarkable as
    // an index over every Single section.
    get("/{$this->cpTrigger}/content/entries/singles?".http_build_query(['viewMode' => 'cards']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', 'singles')
            ->where('pagination.total', 2)
        );
});

it('renders only the default columns unless specific columns are requested', function () {
    EntryModel::factory()->create();

    // `slug` is a valid column but not among the entry defaults.
    get("/{$this->cpTrigger}/content/entries")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('data.0.title')
            ->missing('data.0.slug')
        );

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'columns' => ['slug'],
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('data.0.title')
            ->has('data.0.slug')
        );
});

it('ignores unknown requested columns', function () {
    EntryModel::factory()->create();

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'columns' => ['nope', 'slug'],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('data.0.slug')
            ->missing('data.0.nope')
        );
});

it('serializes sort options with string values and default directions', function () {
    get("/{$this->cpTrigger}/content/entries")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sortOptions')
            ->where('sortOptions', fn ($options) => collect($options)->isNotEmpty()
                && collect($options)->pluck('value')->contains('title')
                && collect($options)->pluck('value')->contains('postDate')
                && collect($options)->every(
                    fn ($option) => is_string($option['value'])
                        && $option['value'] !== ''
                        && in_array($option['defaultDir'], ['asc', 'desc'], true)
                ))
        );
});

it('serializes tableColumns as a list, unpolluted by the legacy context shape', function () {
    // PrepareElementSourcesVariables puts an assoc-keyed `tableColumns` into
    // the hook context; a recursive merge with the view model's list would
    // interleave them into a JSON object and break the Vue column controls.
    EntryModel::factory()->create();

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tableColumns', function ($columns) {
                $columns = collect($columns)->all();

                return array_is_list($columns)
                    && collect($columns)->every(fn ($column) => isset($column['label'], $column['value']));
            })
        );
});

it('renders title cells as element chips carrying the CP element metadata', function () {
    // The Vue index identifies an element from the DOM by the `element` class
    // and these `data-` attributes — double-click-to-edit reads `data-cp-url`
    // and gates on `data-editable`/`data-trashed`. Only `elementChipHtml()`
    // emits them; the generic `chipHtml()` does not, and swapping back to it
    // silently breaks every element interaction on the index.
    EntryModel::factory()->createElement(['title' => 'Hello']);

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('data', function ($rows) {
                $title = (string) (collect($rows)->first()['title'] ?? '');

                return preg_match('/class="[^"]*\belement\b/', $title) === 1
                    && str_contains($title, 'data-cp-url=')
                    && str_contains($title, 'data-editable');
            })
        );
});
