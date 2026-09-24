<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\StructuresController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\CmsAssets;
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
use function Pest\Laravel\postJson;

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

it('includes saved unpublished drafts in entry indexes', function () {
    $entry = EntryModel::factory()->createElement();
    $draft = app(EntryElement::class);
    $draft->siteId = $entry->siteId;
    $draft->sectionId = $entry->sectionId;
    $draft->typeId = $entry->typeId;
    $draft->title = 'Awaiting Review';
    $draft->slug = 'awaiting-review';
    $draft->setAuthorIds([auth()->id()]);
    app(Drafts::class)->saveElementAsDraft($draft, auth()->id());

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('data', fn ($entries) => collect($entries)->contains('id', $draft->id))
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

it('emits a level and descendant flag per row in structure mode', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    $parent = EntryModel::factory()->forSection($section)->create();
    $child = EntryModel::factory()->forSection($section)->create();

    $parentElement = EntryElement::find()->id($parent->id)->one();
    Structures::appendToRoot($structure->id, $parentElement);
    Structures::append($structure->id, EntryElement::find()->id($child->id)->one(), $parentElement);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'structure',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('data.0.id', $parent->id)
            ->where('data.0.level', 1)
            ->where('data.0.hasDescendants', true)
            ->where('data.1.id', $child->id)
            ->where('data.1.level', 2)
            ->where('data.1.hasDescendants', false)
            ->where('data.0.siteId', $parentElement->siteId)
            ->where('data.0.label', $parentElement->getUiLabel())
            ->where('structure.maxLevels', null)
        );
});

it('authorizes structure moves once the structure index has loaded', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    $a = EntryModel::factory()->forSection($section)->create();
    $b = EntryModel::factory()->forSection($section)->create();

    foreach ([$a, $b] as $entry) {
        Structures::appendToRoot($structure->id, EntryElement::find()->id($entry->id)->one());
    }

    $moveRequest = [
        'structureId' => $structure->id,
        'elementId' => $a->id,
        'siteId' => Sites::getPrimarySite()->id,
        'prevId' => $b->id,
    ];

    postJson(action([StructuresController::class, 'moveElement']), $moveRequest)->assertForbidden();

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'structure',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('structure.editable', true));

    postJson(action([StructuresController::class, 'moveElement']), $moveRequest)->assertOk();

    expect(EntryElement::find()->structureId($structure->id)->orderBy('lft')->ids())
        ->toBe([$b->id, $a->id]);
});

it('drops the toggle from a parent once its visible children are gone', function (bool $collapsed) {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    [$parent, $child, $trashed] = EntryModel::factory()->forSection($section)->count(3)->create()->all();
    $find = fn (EntryModel $entry) => EntryElement::find()->id($entry->id)->one();

    Structures::appendToRoot($structure->id, $find($parent));
    Structures::append($structure->id, $find($child), $find($parent));
    Structures::append($structure->id, $find($trashed), $find($parent));

    // Trashing leaves a gap in the parent's nested-set bounds, and moving the
    // last visible child out leaves the parent with nothing to expand.
    app(Elements::class)->deleteElement($find($trashed));
    Structures::moveAfter($structure->id, $find($child), $find($parent));

    $bounds = DB::table(Table::STRUCTUREELEMENTS)->where('elementId', $parent->id)->first();
    expect($bounds->rgt - $bounds->lft)->toBeGreaterThan(1);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'structure',
        'collapsedElementIds' => $collapsed ? [$parent->id] : [],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('data.0.id', $parent->id)
            ->where('data.0.hasDescendants', false)
        );
})->with(['expanded' => false, 'collapsed' => true]);

it('keeps the toggle on a collapsed parent with visible children', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    [$parent, $child] = EntryModel::factory()->forSection($section)->count(2)->create()->all();
    $parentElement = EntryElement::find()->id($parent->id)->one();

    Structures::appendToRoot($structure->id, $parentElement);
    Structures::append($structure->id, EntryElement::find()->id($child->id)->one(), $parentElement);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'structure',
        'collapsedElementIds' => [$parent->id],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('data', 1)
            ->where('data.0.hasDescendants', true)
        );
});

it('excludes the descendants of collapsed elements in structure mode', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    $parent = EntryModel::factory()->forSection($section)->create();
    $child = EntryModel::factory()->forSection($section)->create();

    $parentElement = EntryElement::find()->id($parent->id)->one();
    Structures::appendToRoot($structure->id, $parentElement);
    Structures::append($structure->id, EntryElement::find()->id($child->id)->one(), $parentElement);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'structure',
        'collapsedElementIds' => [$parent->id],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('data', 1)
            ->where('data.0.id', $parent->id)
            ->where('pagination.total', 1)
        );
});

it('keeps a flat table unfiltered when collapsed ids are sent outside structure mode', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);

    $parent = EntryModel::factory()->forSection($section)->create();
    $child = EntryModel::factory()->forSection($section)->create();

    $parentElement = EntryElement::find()->id($parent->id)->one();
    Structures::appendToRoot($structure->id, $parentElement);
    Structures::append($structure->id, EntryElement::find()->id($child->id)->one(), $parentElement);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
        'viewMode' => 'cards',
        'collapsedElementIds' => [$parent->id],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 2)
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

it('crumbs the “all entries” source by name on the bare index', function () {
    // The bare index opens on the “all entries” source, which gets a crumb of
    // its own — and, being what the index itself shows, is addressed by the
    // index's own URL rather than a `?source=*` query.
    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('crumbs', 2)
            ->where('crumbs.0.label', 'Entries')
            ->where('crumbs.0.href', fn ($href) => str_ends_with((string) $href, "/{$this->cpTrigger}/content/entries"))
            ->where('crumbs.1.label', 'All entries')
            ->where('crumbs.1.href', fn ($href) => str_ends_with((string) $href, "/{$this->cpTrigger}/content/entries"))
        );
});

it('adds a section crumb that links the section’s own index URL', function () {
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);

    get("/{$this->cpTrigger}/content/entries/{$section->handle}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('crumbs', 2)
            ->where('crumbs.0.label', 'Entries')
            ->where('crumbs.1.label', 'Blog')
            // The same URL Section::getCpIndexUri() hands the rest of the CP,
            // not a `?source=` query — a crumb shouldn't link a section by a
            // different URL than the sidebar and the edit screen do.
            ->where('crumbs.1.href', fn ($href) => str_ends_with((string) $href, "/{$this->cpTrigger}/content/entries/blog"))
        );
});

it('resolves the section crumb from a ?source= query too', function () {
    // How the sidebar navigates: same screen, source in the query rather than
    // the path.
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$section->uid}",
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('crumbs', 2)
            ->where('crumbs.1.label', 'Blog')
        );
});

it('hangs a source switcher off the section crumb', function () {
    Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);
    Section::factory()->create(['name' => 'News', 'handle' => 'news']);

    get("/{$this->cpTrigger}/content/entries/blog")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs.1.items', function ($actions) {
                $actions = collect($actions);

                $choices = $actions->flatMap(
                    fn (array $action): array => $action['type'] === 'group' ? $action['items'] : [$action],
                );

                // Link action items, with exactly the current one flagged —
                // the shape Breadcrumbs.vue feeds to its ActionMenu.
                return $choices->pluck('label')->contains('Blog')
                    && $choices->pluck('label')->contains('News')
                    // The bare index is reachable from the menu too.
                    && $choices->pluck('label')->contains('All entries')
                    && $choices->every(fn ($action) => $action['type'] === 'link' && ! empty($action['href']))
                    && $choices->where('selected', true)->pluck('label')->all() === ['Blog'];
            })
        );
});

it('gives the source crumb the same list the sources sidebar shows', function () {
    Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);
    Section::factory()->create(['name' => 'News', 'handle' => 'news']);

    get("/{$this->cpTrigger}/content/entries/blog")
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            /** @var array<int, array<string, mixed>> $sources */
            $sources = $page->toArray()['props']['sources'];
            /** @var array<int, array<string, mixed>> $actions */
            $actions = $page->toArray()['props']['crumbs'][1]['items'];

            // The switcher and the sidebar are the same list in two places, so
            // the headings and their order have to survive into the menu —
            // they used to be dropped, leaving an ungrouped run of sources
            // next to a grouped sidebar.
            $outline = fn (array $list): array => collect($list)
                ->flatMap(fn (array $entry): array => match ($entry['type']) {
                    'heading', 'group' => ['# '.($entry['heading'] ?? '')],
                    default => [$entry['label']],
                })
                ->all();

            $menuOutline = collect($actions)
                ->flatMap(fn (array $action): array => $action['type'] === 'group'
                    ? ['# '.$action['heading'], ...collect($action['items'])->pluck('label')->all()]
                    : [$action['label']])
                ->all();

            expect($menuOutline)->toBe($outline($sources));
        });
});

it('titles the screen after the selected source', function () {
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);

    // The bare index is showing “all entries”, and says so.
    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('title', 'All entries'));

    // A section index is named for the section, not the screen — the screen is
    // already named by the crumb above it.
    get("/{$this->cpTrigger}/content/entries/{$section->handle}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Blog')
            ->where('crumbs.0.label', 'Entries')
        );
});

it('scopes the index to the primary site by default', function () {
    $primary = Sites::getCurrentSite();
    $other = Site::factory()->create();

    // Sections are created on the primary site; only the second one is also
    // turned on for the other site.
    $primaryOnly = Section::factory()->create(['type' => SectionType::Channel]);
    $bothSites = Section::factory()->withSites($other)->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($primaryOnly)->create();
    EntryModel::factory()->forSection($bothSites)->count(3)->create();

    get("/{$this->cpTrigger}/content/entries?viewMode=cards")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('siteId', $primary->id)
            ->where('pagination.total', 4)
            ->where('sources', fn ($sources) => collect($sources)->pluck('key')
                ->contains("section:{$primaryOnly->uid}"))
        );
});

it('scopes the index to the site named in the query', function () {
    $other = Site::factory()->create();

    $primaryOnly = Section::factory()->create(['type' => SectionType::Channel]);
    $bothSites = Section::factory()->withSites($other)->create(['type' => SectionType::Channel]);

    EntryModel::factory()->forSection($primaryOnly)->createElement();
    EntryModel::factory()->forSection($bothSites)->createElement();

    // The other site never had the first section, so its source is gone from
    // the list — and the query is scoped to that site too, so the entries,
    // which these factories only ever create on the primary site, are gone
    // with it. Unscoped, this index would still be showing both of them.
    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'site' => $other->handle,
        'viewMode' => 'cards',
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('siteId', $other->id)
            ->where('pagination.total', 0)
            ->where('sources', fn ($sources) => collect($sources)->pluck('key')
                ->contains("section:{$bothSites->uid}"))
            ->where('sources', fn ($sources) => collect($sources)->pluck('key')
                ->doesntContain("section:{$primaryOnly->uid}"))
        );
});

it('falls back off a source that the requested site hides', function () {
    $other = Site::factory()->create();
    $primaryOnly = Section::factory()->create(['type' => SectionType::Channel]);
    Section::factory()->withSites($other)->create(['type' => SectionType::Channel]);

    // The source is asked for by name, but it doesn't exist on that site, so
    // the index resolves a visible one rather than listing nothing.
    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'source' => "section:{$primaryOnly->uid}",
        'site' => $other->handle,
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', fn (string $key) => $key !== "section:{$primaryOnly->uid}")
        );
});

it('leads the crumbs with a shared site switcher on a multi-site install', function () {
    $primary = Sites::getCurrentSite();
    // Same group as the primary site, so the switcher lists the sites flat
    // rather than grouped, and sorted after it.
    $other = Site::factory()->create([
        'groupId' => $primary->groupId,
        'sortOrder' => 99,
    ]);

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Shared chrome rather than one of the index's own crumbs, so it
            // leads the trail on every screen and not just this one.
            ->where('craft.siteCrumb.id', 'site-crumb')
            ->where('craft.siteCrumb.label', $primary->name)
            ->where('craft.siteCrumb.items', fn ($items) => collect($items)->pluck('label')->all() === [
                $primary->name,
                $other->name,
            ])
            // A crumb with a URL has its menu rebuilt from that URL's nav
            // level, which would swap these sites out for the main navigation.
            ->where('craft.siteCrumb.href', null)
            // The client fetches `<name>.svg` straight from the icon assets
            // with none of PHP's alias map, so the name has to be a real file
            // — `world` is the Craft 5 alias and 404s in the browser.
            ->where('craft.siteCrumb.icon', function (string $icon) {
                expect($icon)->not->toBe('world');
                expect(CmsAssets::resourcesPath("icons/solid/{$icon}.svg"))->toBeFile();

                return true;
            })
            // The index still names itself.
            ->where('crumbs.0.label', 'Entries')
        );
});

it('points each site switcher option at the page you are on', function () {
    // Same group as the primary site, so the switcher lists the sites flat
    // rather than grouped, and sorted after it.
    $other = Site::factory()->create([
        'groupId' => Sites::getPrimarySite()->groupId,
        'sortOrder' => 99,
    ]);
    $section = Section::factory()->create(['handle' => 'blog']);

    get("/{$this->cpTrigger}/content/entries/{$section->handle}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.siteCrumb.items', function ($items) use ($other) {
                $hrefs = collect($items)->pluck('href');

                // Switching sites stays put rather than dropping you back on
                // the index root.
                expect($hrefs)->each->toContain('/content/entries/blog');
                expect($hrefs->last())->toContain("site={$other->handle}");

                return true;
            })
        );
});

it('leaves the crumbs alone on a single-site install', function () {
    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs.0.label', 'Entries')
            ->where('sites', [])
        );
});

it('lists only the new site’s sources in the navigation after a switch', function () {
    $other = Site::factory()->create();

    $primaryOnly = Section::factory()->create(['name' => 'Primary only', 'handle' => 'primary-only']);
    $bothSites = Section::factory()->withSites($other)->create(['name' => 'Both sites', 'handle' => 'both-sites']);

    get("/{$this->cpTrigger}/content/entries?site={$other->handle}")
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($primaryOnly, $bothSites) {
            $entries = collect($page->toArray()['props']['craft']['nav'])
                ->firstWhere('label', 'Entries');

            // The nav is the sources sidebar on an index page, so it has to
            // agree with the index about which sources the site has.
            $labels = collect($entries['subnav'])
                ->flatMap(fn (array $item): array => $item['group']
                    ? collect($item['subnav'])->pluck('label')->all()
                    : [$item['label']]);

            expect($labels)->toContain($bothSites->name)
                ->and($labels)->not->toContain($primaryOnly->name);

            return true;
        });
});

it('keeps every source in the navigation on a single-site install', function () {
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($section) {
            $entries = collect($page->toArray()['props']['craft']['nav'])
                ->firstWhere('label', 'Entries');

            $labels = collect($entries['subnav'])
                ->flatMap(fn (array $item): array => $item['group']
                    ? collect($item['subnav'])->pluck('label')->all()
                    : [$item['label']]);

            expect($labels)->toContain($section->name);

            return true;
        });
});
