<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Tests\TestClasses\Field\ModeThumbnailField;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('selects fit for index tiles and crop for inline cards', function (string $viewMode, string $key, string $mode, int $size) {
    $entry = EntryModel::factory()->withField('thumbnail', ModeThumbnailField::class, value: $mode)
        ->createElementWithFields()->element;
    $layout = $entry->getFieldLayout();
    $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
    expect(Fields::saveLayout($layout))->toBeTrue();

    get("/{$this->cpTrigger}/content/entries?viewMode={$viewMode}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('data.0.id', $entry->id)
            ->where("data.0.{$key}", function (string $html) use ($mode, $size) {
                expect($html)->toContainTag('craft-thumbnail', ['mode' => $mode, 'sizes' => "calc({$size}rem/16)"]);

                return true;
            })
        );
})->with([
    'tiles' => ['thumbs', 'thumbHtml', 'fit', 200],
    'inline cards' => ['cards', 'cardContentHtml', 'crop', 120],
]);

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

it('does not render element chips when only pagination is requested', function () {
    EntryModel::factory()->count(3)->create();

    $this->partialMock(ElementHtml::class, fn (MockInterface $mock) => $mock
        ->shouldReceive('elementChipHtml')->never());

    get("/{$this->cpTrigger}/content/entries", [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'content/Index',
        'X-Inertia-Partial-Data' => 'pagination',
    ])
        ->assertOk()
        ->assertJsonPath('props.pagination.total', 3)
        ->assertJsonMissingPath('props.data');
});

it('selects identity presence with users without separate lookups when rendering entry pages', function (bool $showAuthors) {
    $section = Section::factory()->create();
    $type = EntryType::factory()->create();
    $authors = UserModel::factory()->count(4)->active()->create();

    foreach ([0, 1, 2, 0, 3] as $index => $authorIndex) {
        EntryModel::factory()->forSection($section)->forEntryType($type)
            ->title("Article $index")
            ->create()
            ->authors()->attach($authors[$authorIndex]->id, ['sortOrder' => 1]);
    }

    DB::enableQueryLog();

    get(route('craft.cp.content.index', [
        'page' => 'entries',
        'sectionHandle' => $section->handle,
        'columns' => [$showAuthors ? 'authors' : 'id'],
        'sort' => [['field' => 'title', 'direction' => 'asc']],
        'per_page' => 4,
    ]), [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'content/Index',
        'X-Inertia-Partial-Data' => 'data,pagination',
    ])
        ->assertOk()
        ->assertJsonCount(4, 'props.data');

    $queries = array_values(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], Table::SSO_IDENTITIES)));
    DB::disableQueryLog();

    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toContain('hasSsoIdentity');

    if ($showAuthors) {
        expect($queries[0]['bindings'])->toEqualCanonicalizing($authors->take(3)->modelKeys());
    }
})->with(['visible authors' => true, 'hidden authors' => false]);

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

it('scopes the list to the current page or explicitly selected source', function (string $indexPage, ?string $source, int $total) {
    $a = Section::factory()->create(['type' => SectionType::Channel]);
    $b = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($a)->create();
    EntryModel::factory()->forSection($b)->count(3)->create();

    app(ProjectConfig::class)->set(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.EntryElement::class, [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'First'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$a->uid", 'page' => 'First'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$b->uid", 'page' => 'Second'],
    ]);

    get("/{$this->cpTrigger}/content/$indexPage?".http_build_query([
        'source' => $source === 'first' ? "section:$a->uid" : $source,
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', $total)
            ->has('data', $total)
            ->where('source.key', $source === 'first' ? "section:$a->uid" : "section:$b->uid")
        );
})->with([
    'explicit' => ['first', 'first', 1],
    'second page default' => ['second', null, 3],
    'second page fallback' => ['second', 'missing', 3],
    'explicit outside page' => ['second', 'first', 1],
]);

it('scopes the Singles source to single sections only', function () {
    $single = Section::factory()->create(['type' => SectionType::Single]);
    $channel = Section::factory()->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($single)->create();
    EntryModel::factory()->forSection($channel)->count(3)->create();

    // The Singles source must not spill the channel's entries into the list.
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
            ->where('source.structureId', $structure->id)
            ->where('sort.0.field', 'structure')
            ->where('structure.id', $structure->id)
            ->where('viewModes', fn ($modes) => collect($modes)->contains(
                fn (array $mode): bool => $mode['mode'] === 'structure' && $mode['structuresOnly'] === true,
            ))
            ->where('sortOptions', fn ($options) => collect($options)->contains(
                fn (array $option): bool => $option === [
                    'label' => 'Structure',
                    'value' => 'structure',
                    'defaultDir' => 'asc',
                ],
            ))
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

it('selects the singles source for the singles handle', function () {
    Section::factory()->create(['type' => SectionType::Single]);

    get("/{$this->cpTrigger}/content/entries/singles")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', 'singles')
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
