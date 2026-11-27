<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Events\ElementQueryCacheTagsResolving;
use CraftCms\Cms\Element\Queries\Events\ElementQueryPrepared;
use CraftCms\Cms\Element\Queries\Events\ElementQueryPreparing;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Search\Events\SearchPerformed;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\travelTo;

it('gathers cache tags used after a query was executed', function () {
    ElementCaches::startCollectingCacheInfo();

    $entry = Entry::factory()->create();

    entryQuery()->id($entry->id)->all();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.$entry->id);
});

it('only adds ids when less than 100 ids have been requested', function () {
    ElementCaches::startCollectingCacheInfo();

    entryQuery()->id(range(1, 100))->all();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::1');
    expect($dependency->tags)->toContain('element::100');

    ElementCaches::startCollectingCacheInfo();

    entryQuery()->id(range(1, 101))->all();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->not()->toContain('element::1');
    expect($dependency->tags)->not()->toContain('element::100');
});

it('can define extra cache tags', function () {
    ElementCaches::startCollectingCacheInfo();

    Event::listen(ElementQueryCacheTagsResolving::class, function (ElementQueryCacheTagsResolving $event) {
        $event->tags[] = 'foo';
    });

    entryQuery()->all();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain(sprintf(
        'element::%s::%s',
        EntryElement::class,
        'foo',
    ));
});

it('gathers cache tags when preparing a query aborts it', function (string $method) {
    $entry = Entry::factory()->create();

    ElementCaches::startCollectingCacheInfo();
    entryQuery()->sectionId($entry->sectionId)->descendantOf(999999)->$method();
    [$dependency] = ElementCaches::stopCollectingCacheInfo();

    expect($dependency)->not->toBeNull()
        ->and($dependency->tags)->toContain(sprintf('element::%s::section:%s', EntryElement::class, $entry->sectionId));
})->with(['all', 'count', 'exists', 'ids']);

it('gathers cache tags when a query returns its result override', function () {
    $entry = Entry::factory()->create();
    $query = entryQuery()->sectionId($entry->sectionId);
    $query->setResultOverride([]);

    ElementCaches::startCollectingCacheInfo();
    $query->all();
    [$dependency] = ElementCaches::stopCollectingCacheInfo();

    expect($dependency)->not->toBeNull()
        ->and($dependency->tags)->toContain(sprintf('element::%s::section:%s', EntryElement::class, $entry->sectionId));
});

it('registers when an entry the query matches expires, whatever the query’s shape', function (Closure $query) {
    travelTo(Date::parse('2026-01-01 09:30:00'));
    $live = Entry::factory()->create(['postDate' => Date::parse('2026-01-01 09:00:00')]);
    $expiring = Entry::factory()->forSection($live->section)->create([
        'postDate' => Date::parse('2026-01-01 09:00:00'),
        'expiryDate' => Date::parse('2026-01-01 10:00:30'),
    ]);
    $structure = Structure::factory()->create();
    $structure->structureElements()->delete();
    $root = new StructureElement(['structureId' => $structure->id, 'elementId' => $live->id]);
    $root->makeRoot();
    new StructureElement(['structureId' => $structure->id, 'elementId' => $expiring->id])->appendTo($root);

    ElementCaches::startCollectingCacheInfo();
    $query($live->id, $expiring->id, $structure->id);
    [, $duration] = ElementCaches::stopCollectingCacheInfo();

    expect($duration)->toBe(1800);
})->with([
    'orWhere' => [fn (int $live, int $expiring) => entryQuery()->where('elements.id', $live)->orWhere('elements.id', $expiring)->count()],
    'union' => [fn (int $live, int $expiring) => entryQuery()->id($expiring)->union(entryQuery()->id($live)->toBase())->count()],
    'groupBy' => [fn () => entryQuery()->select(['entries.typeId'])->selectRaw('count(*) as c')->groupBy('entries.typeId')->reorder('entries.typeId')->asArray()->all()],
    'structure column condition' => [fn (int $live, int $expiring, int $structureId) => entryQuery()->structureId($structureId)->where('structureelements.level', 1)->count()],
    'structure column condition in beforeQuery()' => [fn (int $live, int $expiring, int $structureId) => entryQuery()->structureId($structureId)->beforeQuery(fn ($q) => $q->where('structureelements.level', 1))->count()],
]);

it('leaves the cache to its usual duration when a query depends on a listener’s join', function () {
    travelTo(Date::parse('2026-01-01 09:30:00'));
    Entry::factory()->create(['postDate' => Date::parse('2026-01-01 09:00:00')]);
    Entry::factory()->create(['postDate' => Date::parse('2026-01-01 10:00:30')]);
    Event::listen(ElementQueryPreparing::class, fn (ElementQueryPreparing $event) => $event->query->leftJoin('sites as listenersites', 'listenersites.id', '=', 'elements_sites.siteId'));
    Log::spy();

    ElementCaches::startCollectingCacheInfo();
    $count = entryQuery()->where('listenersites.handle', '!=', 'nonexistent')->count();
    [$dependency, $duration] = ElementCaches::stopCollectingCacheInfo();

    expect($count)->toBe(1)
        ->and($duration)->toBeNull()
        ->and($dependency->tags)->toContain('element::'.EntryElement::class);
    Log::shouldHaveReceived('warning')->once();
});

it('doesn’t cache a result that’s already stale when the minute changes as the query runs', function () {
    travelTo(Date::parse('2026-01-01 09:59:59'));
    Entry::factory()->create(['postDate' => Date::parse('2026-01-01 10:00:00')]);
    Event::listen(ElementQueryPrepared::class, fn () => travelTo(Date::parse('2026-01-01 10:00:00')));

    ElementCaches::startCollectingCacheInfo();
    $count = entryQuery()->count();
    [, $duration] = ElementCaches::stopCollectingCacheInfo();

    expect($count)->toBe(0)
        ->and($duration)->toBe(0);
});

it('looks up when entries go live even when a listener sets the query’s status', function () {
    travelTo(Date::parse('2026-01-01 09:30:00'));
    $entry = Entry::factory()->create(['postDate' => Date::parse('2026-01-01 10:00:30')]);
    Event::listen(ElementQueryPreparing::class, fn (ElementQueryPreparing $event) => $event->query->status('live'));

    ElementCaches::startCollectingCacheInfo();
    entryQuery()->sectionId($entry->sectionId)->count();
    [, $duration] = ElementCaches::stopCollectingCacheInfo();

    expect($duration)->toBe(1800);
});

it('keeps nested caches intact when a listener collects cache info while a lookup runs', function () {
    travelTo(Date::parse('2026-01-01 09:30:00'));
    $entry = Entry::factory()->create(['postDate' => Date::parse('2026-01-01 10:00:30')]);
    $related = Entry::factory()->create(['postDate' => Date::parse('2026-01-01 09:00:00')]);
    DB::table(Table::RELATIONS)->insert([
        'fieldId' => Field::factory()->create(['type' => Entries::class])->id,
        'sourceId' => $entry->id,
        'targetId' => $related->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);
    // The relatedTo() subquery is prepared again within each lookup, and dispatches its events
    Event::listen(ElementQueryPreparing::class, function () {
        ElementCaches::startCollectingCacheInfo();
        ElementCaches::stopCollectingCacheInfo();
    });

    ElementCaches::startCollectingCacheInfo();
    ElementCaches::startCollectingCacheInfo();
    entryQuery()->sectionId($entry->sectionId)->relatedTo(entryQuery()->id($related->id))->count();
    [$inner, $innerDuration] = ElementCaches::stopCollectingCacheInfo();
    [$outer, $outerDuration] = ElementCaches::stopCollectingCacheInfo();

    expect($innerDuration)->toBe(1800)
        ->and($outerDuration)->toBe(1800)
        ->and($outer->tags)->toBe($inner->tags);
});

it('doesn’t repeat a query’s search to look up when its results change', function () {
    $entry = Entry::factory()->indexed()->title('Zebra crossing')->create();
    Entry::factory()->forSection($entry->section)->pending()->create();
    $searches = 0;
    Event::listen(SearchPerformed::class, function () use (&$searches) {
        $searches++;
    });

    ElementCaches::startCollectingCacheInfo();
    entryQuery()->sectionId($entry->sectionId)->search('zebra')->orderBy('score')->count();
    [, $duration] = ElementCaches::stopCollectingCacheInfo();

    expect($searches)->toBe(1)
        ->and($duration)->not->toBeNull();
});
