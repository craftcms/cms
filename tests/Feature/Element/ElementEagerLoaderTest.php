<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\DraftActivity;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\ElementsEagerLoading;
use CraftCms\Cms\Element\Operations\ElementEagerLoader;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderDrafts;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderDraftsState;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderElement;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderExpirableTargetElement;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderNestedTargetElement;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderQuery;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderResolvedTargetElement;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderSourceElement;
use CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader\TestElementEagerLoaderTargetElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    TestElementEagerLoaderElement::resetTestState();
    TestElementEagerLoaderQuery::resetTestState();
    TestElementEagerLoaderDraftsState::reset();
});

function invokeElementEagerLoaderMethod(ElementEagerLoader $loader, string $method, mixed ...$arguments): mixed
{
    $reflectionMethod = new ReflectionMethod($loader, $method);

    return $reflectionMethod->invoke($loader, ...$arguments);
}

it('creates eager loading plans from strings arrays aliases and nested paths', function () {
    $loader = app(ElementEagerLoader::class);
    $when = fn (ElementInterface $element) => $element->id === 1;

    $plans = $loader->createEagerLoadingPlans([
        'assets.transforms',
        ['path' => 'assets.images', 'criteria' => ['limit' => '2', 'count' => true], 'when' => $when],
        ['path' => 'assets as related', 'criteria' => ['siteId' => '2']],
    ]);

    expect($plans)->toHaveCount(2);

    $plansByAlias = collect($plans)->keyBy('alias');

    expect($plansByAlias['assets']->handle)->toBe('assets')
        ->and($plansByAlias['assets']->all)->toBeTrue()
        ->and($plansByAlias['assets']->nested)->toHaveCount(2)
        ->and($plansByAlias['assets']->nested[0]->handle)->toBe('transforms')
        ->and($plansByAlias['assets']->nested[0]->all)->toBeTrue()
        ->and($plansByAlias['assets']->nested[1]->handle)->toBe('images')
        ->and($plansByAlias['assets']->nested[1]->count)->toBeTrue()
        ->and($plansByAlias['assets']->nested[1]->criteria)->toBe(['limit' => '2'])
        ->and($plansByAlias['assets']->nested[1]->when)->toBe($when)
        ->and($plansByAlias['related']->handle)->toBe('assets')
        ->and($plansByAlias['related']->alias)->toBe('related')
        ->and($plansByAlias['related']->criteria)->toBe(['siteId' => '2'])
        ->and($plansByAlias['related']->all)->toBeTrue();
});

it('normalizes eager load plan objects recursively', function () {
    $loader = app(ElementEagerLoader::class);

    $plans = $loader->createEagerLoadingPlans([
        new EagerLoadPlan(
            handle: 'related',
            alias: 'relatedAlias',
            nested: [
                new EagerLoadPlan(handle: 'children'),
            ],
        ),
    ]);

    expect($plans)->toHaveCount(1)
        ->and($plans[0]->alias)->toBe('relatedAlias')
        ->and($plans[0]->all)->toBeTrue()
        ->and($plans[0]->nested)->toHaveCount(1)
        ->and($plans[0]->nested[0]->handle)->toBe('children')
        ->and($plans[0]->nested[0]->all)->toBeTrue();
});

it('eager loads counts without hydrating elements', function () {
    $loader = app(ElementEagerLoader::class);
    $sourceA = new TestElementEagerLoaderSourceElement(['id' => 1]);
    $sourceB = new TestElementEagerLoaderSourceElement(['id' => 2]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('counted', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'map' => [
            ['source' => 1, 'target' => 101],
            ['source' => 1, 'target' => 102],
            ['source' => 2, 'target' => 101],
        ],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 101, 'siteId' => 1],
        ['id' => 101, 'siteId' => 1],
        ['id' => 102, 'siteId' => 1],
    ]);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$sourceA, $sourceB], [
        ['path' => 'counted', 'count' => true],
    ]);

    expect($sourceA->getEagerLoadedElementCount('counted'))->toBe(3)
        ->and($sourceB->getEagerLoadedElementCount('counted'))->toBe(2)
        ->and($sourceA->getEagerLoadedElements('counted'))->toBeNull()
        ->and(TestElementEagerLoaderQuery::$afterHydrateCalls)->toBe([]);
});

it('eager loads nested elements per site and collects cache expiry data', function () {
    $loader = app(ElementEagerLoader::class);
    $elementCaches = app(ElementCaches::class);
    $sourceA = new TestElementEagerLoaderSourceElement(['id' => 1, 'siteId' => 1]);
    $sourceB = new TestElementEagerLoaderSourceElement(['id' => 2, 'siteId' => 1]);
    $sourceC = new TestElementEagerLoaderSourceElement(['id' => 3, 'siteId' => 2]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('related', function (array $sourceElements) {
        $map = [];

        foreach ($sourceElements as $sourceElement) {
            $map = array_merge($map, match ($sourceElement->id) {
                1 => [
                    ['source' => 1, 'target' => 201],
                    ['source' => 1, 'target' => 202],
                ],
                3 => [
                    ['source' => 3, 'target' => 301],
                ],
                default => [],
            });
        }

        return [
            'elementType' => TestElementEagerLoaderExpirableTargetElement::class,
            'map' => $map,
        ];
    });

    TestElementEagerLoaderExpirableTargetElement::setTestEagerLoadingMap('child', function (array $sourceElements) {
        $map = [];

        foreach ($sourceElements as $sourceElement) {
            $map = array_merge($map, match ($sourceElement->id) {
                202 => [
                    ['source' => 202, 'target' => 401],
                ],
                301 => [
                    ['source' => 301, 'target' => 402],
                ],
                default => [],
            });
        }

        return [
            'elementType' => TestElementEagerLoaderNestedTargetElement::class,
            'map' => $map,
        ];
    });

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderExpirableTargetElement::class, [
        ['id' => 201, 'siteId' => 1, 'title' => 'Alpha', 'expiryDate' => new DateTime('+10 minutes')],
        ['id' => 202, 'siteId' => 1, 'title' => 'Beta', 'expiryDate' => new DateTime('+5 minutes')],
        ['id' => 301, 'siteId' => 2, 'title' => 'Gamma', 'expiryDate' => new DateTime('+15 minutes')],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderNestedTargetElement::class, [
        ['id' => 401, 'siteId' => 1, 'title' => 'Child A'],
        ['id' => 402, 'siteId' => 2, 'title' => 'Child B'],
    ]);

    $elementCaches->startCollectingCacheInfo();

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$sourceA, $sourceB, $sourceC], [
        new EagerLoadPlan(
            handle: 'related',
            alias: 'related',
            all: true,
            count: true,
            lazy: true,
            nested: [
                new EagerLoadPlan(
                    handle: 'child',
                    alias: 'child',
                    all: true,
                ),
            ],
        ),
    ]);

    [, $duration] = $elementCaches->stopCollectingCacheInfo();

    $relatedForSourceA = $sourceA->getEagerLoadedElements('related');
    $relatedForSourceB = $sourceB->getEagerLoadedElements('related');
    $relatedForSourceC = $sourceC->getEagerLoadedElements('related');

    expect(TestElementEagerLoaderSourceElement::eagerLoadingCalls())->toHaveCount(2)
        ->and(TestElementEagerLoaderSourceElement::eagerLoadingCalls()[0]['ids'])->toBe([1, 2])
        ->and(TestElementEagerLoaderSourceElement::eagerLoadingCalls()[1]['ids'])->toBe([3])
        ->and($relatedForSourceA)->not->toBeNull()
        ->and($relatedForSourceA?->pluck('id')->all())->toBe([201, 202])
        ->and($sourceA->getEagerLoadedElementCount('related'))->toBe(2)
        ->and($relatedForSourceB)->not->toBeNull()
        ->and($relatedForSourceB?->all())->toBe([])
        ->and($sourceB->getEagerLoadedElementCount('related'))->toBe(0)
        ->and($relatedForSourceC?->pluck('id')->all())->toBe([301])
        ->and($sourceC->getEagerLoadedElementCount('related'))->toBe(1)
        ->and($relatedForSourceA?->last()?->getEagerLoadedElements('child')?->pluck('id')->all())->toBe([401])
        ->and($relatedForSourceC?->first()?->getEagerLoadedElements('child')?->pluck('id')->all())->toBe([402])
        ->and($relatedForSourceA?->first()?->eagerLoadInfo?->plan->handle)->toBe('related')
        ->and(array_merge(...TestElementEagerLoaderQuery::$afterHydrateCalls[TestElementEagerLoaderExpirableTargetElement::class]))->toBe([201, 202, 301])
        ->and(array_merge(...TestElementEagerLoaderQuery::$afterHydrateCalls[TestElementEagerLoaderNestedTargetElement::class]))->toBe([401, 402])
        ->and($duration)->toBeInt()
        ->and($duration)->toBeGreaterThan(0)
        ->and($duration)->toBeLessThanOrEqual(300);
});

it('skips plans when their filter removes every source element and when maps resolve to null', function () {
    $loader = app(ElementEagerLoader::class);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('missing', null);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], [
        new EagerLoadPlan(
            handle: 'filtered',
            alias: 'filtered',
            all: true,
            when: fn () => false,
        ),
        new EagerLoadPlan(
            handle: 'missing',
            alias: 'missing',
            all: true,
        ),
    ]);

    expect(TestElementEagerLoaderSourceElement::eagerLoadingCalls())->toHaveCount(1)
        ->and(TestElementEagerLoaderSourceElement::eagerLoadingCalls()[0]['handle'])->toBe('missing')
        ->and($source->getEagerLoadedElements('filtered'))->toBeNull()
        ->and($source->getEagerLoadedElements('missing'))->toBeNull();
});

it('allows before eager load listeners to replace the plans', function () {
    $loader = app(ElementEagerLoader::class);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('replacement', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'map' => [
            ['source' => 1, 'target' => 701],
        ],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 701, 'siteId' => 1, 'title' => 'Replacement'],
    ]);

    Event::listen(ElementsEagerLoading::class, function (ElementsEagerLoading $event) {
        if ($event->elementType !== TestElementEagerLoaderSourceElement::class) {
            return;
        }

        $event->with = [
            new EagerLoadPlan(
                handle: 'replacement',
                alias: 'replacement',
                all: true,
            ),
        ];
    });

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], 'ignored');

    expect(TestElementEagerLoaderSourceElement::eagerLoadingCalls())->toHaveCount(1)
        ->and(TestElementEagerLoaderSourceElement::eagerLoadingCalls()[0]['handle'])->toBe('replacement')
        ->and($source->getEagerLoadedElements('replacement')?->pluck('id')->all())->toBe([701])
        ->and($source->getEagerLoadedElements('ignored'))->toBeNull();
});

it('applies explicit query ids before where in constraints', function () {
    $loader = app(ElementEagerLoader::class);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('filtered', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'map' => [
            ['source' => 1, 'target' => 501],
            ['source' => 1, 'target' => 502],
            ['source' => 1, 'target' => 503],
        ],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 501, 'siteId' => 1, 'title' => 'Alpha'],
        ['id' => 502, 'siteId' => 1, 'title' => 'Beta'],
        ['id' => 503, 'siteId' => 1, 'title' => 'Gamma'],
    ]);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], [
        ['path' => 'filtered', 'criteria' => ['id' => [502, 503, 999]]],
    ]);

    expect($source->getEagerLoadedElements('filtered')?->pluck('id')->all())->toBe([502, 503])
        ->and(TestElementEagerLoaderQuery::$whereInCalls[TestElementEagerLoaderTargetElement::class][0])->toBe([501, 502, 503]);
});

it('lets mapping criteria take precedence except for siteId criteria', function () {
    $loader = app(ElementEagerLoader::class);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('mapped', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'criteria' => ['siteId' => 1],
        'map' => [
            ['source' => 1, 'target' => 801],
            ['source' => 1, 'target' => 802],
        ],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 801, 'siteId' => 1, 'title' => 'Alpha'],
        ['id' => 802, 'siteId' => 2, 'title' => 'Beta'],
    ]);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], [
        ['path' => 'mapped', 'criteria' => ['siteId' => 2]],
    ]);

    expect($source->getEagerLoadedElements('mapped')?->pluck('id')->all())->toBe([802])
        ->and(TestElementEagerLoaderQuery::$querySiteIds[TestElementEagerLoaderTargetElement::class][0])->toBe(2);
});

it('lets non-site mapping criteria take precedence', function () {
    $loader = app(ElementEagerLoader::class);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('mapped', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'criteria' => ['id' => 801],
        'map' => [
            ['source' => 1, 'target' => 801],
            ['source' => 1, 'target' => 802],
        ],
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 801, 'siteId' => 1, 'title' => 'Alpha'],
        ['id' => 802, 'siteId' => 1, 'title' => 'Beta'],
    ]);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], [
        ['path' => 'mapped', 'criteria' => ['id' => 802]],
    ]);

    expect($source->getEagerLoadedElements('mapped')?->pluck('id')->all())->toBe([801])
        ->and(TestElementEagerLoaderQuery::$whereInCalls[TestElementEagerLoaderTargetElement::class][0])->toBe([801, 802]);
});

it('uses custom element factories and provisional drafts when requested', function () {
    $loader = app(ElementEagerLoader::class, ['drafts' => new TestElementEagerLoaderDrafts(
        app(Elements::class),
        app(DraftActivity::class),
    )]);
    $source = new TestElementEagerLoaderSourceElement(['id' => 1]);

    TestElementEagerLoaderSourceElement::setTestEagerLoadingMap('drafty', [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'map' => [
            ['source' => 1, 'target' => 601],
            ['source' => 1, 'target' => 602],
        ],
        'criteria' => ['withProvisionalDrafts' => true],
        'createElement' => function (ElementQuery $query, array $result, ElementInterface $sourceElement) {
            $element = new $query->elementType($result);
            $element->title = $sourceElement->id.'-'.$result['title'];

            return $element;
        },
    ]);

    TestElementEagerLoaderQuery::setRows(TestElementEagerLoaderTargetElement::class, [
        ['id' => 601, 'siteId' => 1, 'title' => 'Alpha'],
        ['id' => 602, 'siteId' => 1, 'title' => 'Beta'],
    ]);

    $loader->eagerLoadElements(TestElementEagerLoaderSourceElement::class, [$source], 'drafty');

    expect(TestElementEagerLoaderDraftsState::$calls)->toBe(1)
        ->and($source->getEagerLoadedElements('drafty')?->pluck('id')->all())->toBe([602, 601])
        ->and($source->getEagerLoadedElements('drafty')?->pluck('title')->all())->toBe(['1-Beta', '1-Alpha']);
});

it('normalizes private eager loading maps and resolves untyped target ids from the database', function () {
    $loader = app(ElementEagerLoader::class);
    $entry = EntryModel::factory()->create();
    $createElement = static fn () => new TestElementEagerLoaderResolvedTargetElement;

    DB::table(Table::ELEMENTS)
        ->where('id', $entry->id)
        ->update(['type' => TestElementEagerLoaderResolvedTargetElement::class]);

    $directMap = [
        'elementType' => TestElementEagerLoaderTargetElement::class,
        'map' => [],
    ];

    $groupedMaps = invokeElementEagerLoaderMethod($loader, 'normalizeEagerLoadingMaps', [
        'map' => [
            ['source' => 1, 'target' => $entry->id],
            ['source' => 1, 'target' => 999999],
        ],
        'criteria' => ['limit' => 1],
        'createElement' => $createElement,
    ]);

    $nestedMaps = invokeElementEagerLoaderMethod($loader, 'normalizeEagerLoadingMaps', [
        ['map' => []],
        $directMap,
    ]);

    expect(invokeElementEagerLoaderMethod($loader, 'normalizeEagerLoadingMaps', false))->toBe([false])
        ->and(invokeElementEagerLoaderMethod($loader, 'normalizeEagerLoadingMaps', $directMap))->toBe([$directMap])
        ->and(invokeElementEagerLoaderMethod($loader, 'groupMapsByElementType', []))->toBe([])
        ->and($groupedMaps)->toHaveCount(1)
        ->and($groupedMaps[0]['elementType'])->toBe(TestElementEagerLoaderResolvedTargetElement::class)
        ->and($groupedMaps[0]['map'])->toBe([
            ['source' => 1, 'target' => $entry->id],
        ])
        ->and($groupedMaps[0]['criteria'])->toBe(['limit' => 1])
        ->and($groupedMaps[0]['createElement'])->toBe($createElement)
        ->and($nestedMaps)->toBe([$directMap]);
});
