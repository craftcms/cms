<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadInfo;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\BeforeEagerLoadElements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 */
#[Singleton]
readonly class ElementEagerLoader
{
    public function __construct(
        private Drafts $drafts,
        private Elements $elements,
        private ElementCaches $elementCaches,
    ) {}

    /**
     * Normalizes a `with` element query param into an array of eager-loading plans.
     *
     *
     * @phpstan-param string|array<EagerLoadPlan|array|string> $with
     *
     * @return EagerLoadPlan[]
     */
    public function createEagerLoadingPlans(string|array $with): array
    {
        // Normalize the paths and group based on the top level eager loading handle
        if (is_string($with)) {
            $with = str($with)->explode(',');
        }

        $plans = [];
        $nestedWiths = [];

        foreach ($with as $path) {
            // Is this already an EagerLoadPlan object?
            if ($path instanceof EagerLoadPlan) {
                // Make sure $all is true if $count is false
                if (! $path->count && ! $path->all) {
                    $path->all = true;
                }

                // ...recursively for any nested plans
                $path->nested = $this->createEagerLoadingPlans($path->nested);

                // Don't index the plan by its alias, as two plans w/ different `when` filters could be using the same alias.
                // Side effect: mixing EagerLoadPlan objects and arrays could result in redundant element queries,
                // but that would be a weird thing to do.
                $plans[] = $path;

                continue;
            }

            // Separate the path and the criteria
            if (is_array($path)) {
                $criteria = $path['criteria'] ?? $path[1] ?? null;
                $count = $path['count'] ?? Arr::pull($criteria, 'count', false);
                $when = $path['when'] ?? null;
                $path = $path['path'] ?? $path[0];
            } else {
                $criteria = null;
                $count = false;
                $when = null;
            }

            // Split the path into the top segment and subpath
            if (($dot = strpos((string) $path, '.')) !== false) {
                $handle = substr((string) $path, 0, $dot);
                $subpath = substr((string) $path, $dot + 1);
            } else {
                $handle = $path;
                $subpath = null;
            }

            // Get the handle & alias
            if (preg_match('/^([a-zA-Z][a-zA-Z0-9_:]*)\s+as\s+('.HandleRule::$handlePattern.')$/', (string) $handle,
                $match)) {
                $handle = $match[1];
                $alias = $match[2];
            } else {
                $alias = $handle;
            }

            if (! isset($plans[$alias])) {
                $plan = $plans[$alias] = new EagerLoadPlan(
                    handle: $handle,
                    alias: $alias,
                );
            } else {
                $plan = $plans[$alias];
            }

            // Only set the criteria if there's no subpath
            if ($subpath === null) {
                if ($criteria !== null) {
                    $plan->criteria = $criteria;
                }

                if ($count) {
                    $plan->count = true;
                } else {
                    $plan->all = true;
                }

                if ($when !== null) {
                    $plan->when = $when;
                }
            } else {
                // We are for sure going to need to query the elements
                $plan->all = true;

                // Add this as a nested "with"
                $nestedWiths[$alias][] = [
                    'path' => $subpath,
                    'criteria' => $criteria,
                    'count' => $count,
                    'when' => $when,
                ];
            }
        }

        foreach ($nestedWiths as $alias => $withs) {
            $plans[$alias]->nested = $this->createEagerLoadingPlans($withs);
        }

        return array_values($plans);
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param  class-string<ElementInterface>  $elementType  The root element type class
     * @param  ElementInterface[]  $elements  The root element models that should be updated with the eager-loaded elements
     * @param  array<string|array>|string|EagerLoadPlan[]  $with  Dot-delimited paths of the elements that should be eager-loaded into the root elements
     */
    public function eagerLoadElements(string $elementType, array|Collection $elements, array|string $with): void
    {
        $elements = collect($elements);

        // Bail if there aren't even any elements
        if ($elements->isEmpty()) {
            return;
        }

        $elementsBySite = $elements
            ->groupBy(fn (ElementInterface $element) => $element->siteId)
            ->map(fn (Collection $elements) => $elements->all())
            ->all();

        $with = $this->createEagerLoadingPlans($with);
        $this->eagerLoadElementsInternal($elementType, $elementsBySite, $with);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  ElementInterface[][]  $elementsBySite
     * @param  EagerLoadPlan[]  $with
     */
    private function eagerLoadElementsInternal(string $elementType, array $elementsBySite, array $with): void
    {
        foreach ($elementsBySite as $siteId => $elements) {
            $elements = array_values($elements);

            event($event = new BeforeEagerLoadElements($elementType, $elements, $with));

            foreach ($event->with as $plan) {
                // Filter out any elements that the plan doesn't like
                if ($plan->when !== null) {
                    $filteredElements = array_values(array_filter($elements, $plan->when));
                    if (empty($filteredElements)) {
                        continue;
                    }
                } else {
                    $filteredElements = $elements;
                }

                // Get the eager-loading map from the source element type
                $maps = $elementType::eagerLoadingMap($filteredElements, $plan->handle);

                if ($maps === null) {
                    // Null means to skip eager-loading this segment
                    continue;
                }

                // Set everything to empty results as a starting point
                foreach ($filteredElements as $sourceElement) {
                    if ($plan->count) {
                        $sourceElement->setEagerLoadedElementCount($plan->alias, 0);
                    }
                    if ($plan->all) {
                        $sourceElement->setEagerLoadedElements($plan->alias, [], $plan);
                        $sourceElement->setLazyEagerLoadedElements($plan->alias, $plan->lazy);
                    }
                }

                $maps = $this->normalizeEagerLoadingMaps($maps);

                foreach ($maps as $map) {
                    $targetElementIdsBySourceIds = null;
                    $query = null;
                    $offset = 0;
                    $limit = null;

                    if (! empty($map['map'])) {
                        // Loop through the map to find:
                        // - unique target element IDs
                        // - target element IDs indexed by source element IDs
                        $uniqueTargetElementIds = [];
                        $targetElementIdsBySourceIds = [];

                        foreach ($map['map'] as $mapping) {
                            if (! empty($mapping['target'])) {
                                $uniqueTargetElementIds[$mapping['target']] = true;
                                $targetElementIdsBySourceIds[$mapping['source']][$mapping['target']] = true;
                            }
                        }

                        // Get the target elements
                        $query = $this->elements->createElementQuery($map['elementType']);

                        // Default to no order, offset, or limit, but allow the element type/path criteria to override
                        $query->reorder();
                        $query->offset(null);
                        $query->limit(null);

                        $criteria = array_merge(
                            $map['criteria'] ?? [],
                            $plan->criteria,
                        );

                        // Save the offset & limit params for later
                        $offset = Arr::pull($criteria, 'offset', 0);
                        $limit = Arr::pull($criteria, 'limit');

                        Typecast::configure($query, $criteria);

                        if (! $query->siteId) {
                            $query->siteId = $siteId;
                        }

                        if (! $query->id) {
                            $query->id = array_keys($uniqueTargetElementIds);
                        } else {
                            $query->whereIn('elements.id', array_keys($uniqueTargetElementIds));
                        }
                    }

                    // Do we just need the count?
                    if ($plan->count && ! $plan->all) {
                        // Just fetch the target elements’ IDs
                        $targetElementIdCounts = [];
                        if ($query) {
                            foreach ($query->ids() as $id) {
                                if (! isset($targetElementIdCounts[$id])) {
                                    $targetElementIdCounts[$id] = 1;
                                } else {
                                    $targetElementIdCounts[$id]++;
                                }
                            }
                        }

                        // Loop through the source elements and count up their targets
                        foreach ($filteredElements as $sourceElement) {
                            if (! empty($targetElementIdCounts) && isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                                $count = 0;
                                foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                    if (isset($targetElementIdCounts[$targetElementId])) {
                                        $count += $targetElementIdCounts[$targetElementId];
                                    }
                                }
                                if ($count !== 0) {
                                    $sourceElement->setEagerLoadedElementCount($plan->alias, $count);
                                }
                            }
                        }

                        continue;
                    }

                    $targetElementData = $query ? Collection::make($query->asArray()->all())->groupBy('id')->all() : [];
                    $targetElements = [];

                    // Tell the source elements about their eager-loaded elements
                    foreach ($filteredElements as $sourceElement) {
                        $targetElementIdsForSource = [];
                        $targetElementsForSource = [];

                        if (isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                            // Does the path mapping want a custom order?
                            if (! empty($criteria['orderBy']) || ! empty($criteria['order'])) {
                                // Assign the elements in the order they were returned from the query
                                foreach (array_keys($targetElementData) as $targetElementId) {
                                    if (isset($targetElementIdsBySourceIds[$sourceElement->id][$targetElementId])) {
                                        $targetElementIdsForSource[] = $targetElementId;
                                    }
                                }
                            } else {
                                // Assign the elements in the order defined by the map
                                foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                    if (isset($targetElementData[$targetElementId])) {
                                        $targetElementIdsForSource[] = $targetElementId;
                                    }
                                }
                            }

                            if (! empty($criteria['inReverse'])) {
                                $targetElementIdsForSource = array_reverse($targetElementIdsForSource);
                            }

                            // Create the elements
                            $currentOffset = 0;
                            $count = 0;
                            foreach ($targetElementIdsForSource as $elementId) {
                                foreach ($targetElementData[$elementId] as $result) {
                                    if ($offset && $currentOffset < $offset) {
                                        $currentOffset++;

                                        continue;
                                    }
                                    $targetSiteId = $result['siteId'];
                                    if (! isset($targetElements[$targetSiteId][$elementId])) {
                                        if (isset($map['createElement'])) {
                                            $targetElements[$targetSiteId][$elementId] = $map['createElement']($query,
                                                $result, $sourceElement);
                                        } else {
                                            $targetElements[$targetSiteId][$elementId] = $query->createElement($result);
                                        }
                                    }
                                    $targetElementsForSource[] = $element = $targetElements[$targetSiteId][$elementId];

                                    // If we're collecting cache info and the element is expirable, register its expiry date
                                    if (
                                        $element instanceof ExpirableElementInterface &&
                                        $this->elementCaches->isCollectingCacheInfo() &&
                                        ($expiryDate = $element->getExpiryDate()) !== null
                                    ) {
                                        $this->elementCaches->setCacheExpiryDate($expiryDate);
                                    }

                                    if ($limit && ++$count === $limit) {
                                        break 2;
                                    }
                                }
                            }
                        }

                        if (! empty($targetElementsForSource)) {
                            if (! empty($criteria['withProvisionalDrafts'])) {
                                $targetElementsForSource = $this->drafts->withProvisionalDrafts($targetElementsForSource);
                            }

                            $sourceElement->setEagerLoadedElements($plan->alias, $targetElementsForSource, $plan);

                            if ($plan->count) {
                                $sourceElement->setEagerLoadedElementCount($plan->alias, count($targetElementsForSource));
                            }
                        }
                    }

                    if (! empty($targetElements)) {
                        /** @var ElementInterface[] $flatTargetElements */
                        $flatTargetElements = array_merge(...array_values($targetElements));

                        // Set the eager loading info on each of the target elements,
                        // in case it's needed for lazy eager loading
                        $eagerLoadResult = new EagerLoadInfo($plan, $filteredElements);
                        foreach ($flatTargetElements as $element) {
                            $element->eagerLoadInfo = $eagerLoadResult;
                        }

                        // Pass the instantiated elements to afterPopulate()
                        $query->asArray = false;
                        if ($query instanceof ElementQueryInterface) {
                            $query->afterHydrate(collect($flatTargetElements));
                        }
                    }

                    // Now eager-load any sub paths
                    if (! empty($map['map']) && ! empty($plan->nested)) {
                        $this->eagerLoadElementsInternal(
                            $map['elementType'],
                            array_map(array_values(...), $targetElements),
                            $plan->nested,
                        );
                    }
                }
            }
        }
    }

    /**
     * @param  EagerLoadingMap|EagerLoadingMap[]|false  $map
     * @return EagerLoadingMap[]|false[]
     */
    private function normalizeEagerLoadingMaps(array|false $map): array
    {
        if (isset($map['elementType']) || $map === false) {
            // a normal, one-dimensional map
            return [$map];
        }

        if (isset($map['map'])) {
            // no single element type was provided, so split it up into multiple maps - one for each unique type
            /** @phpstan-ignore-next-line */
            $maps = $this->groupMapsByElementType($map['map']);
            if (isset($map['criteria']) || isset($map['createElement'])) {
                foreach ($maps as &$m) {
                    $m['criteria'] ??= $map['criteria'] ?? [];
                    $m['createElement'] ??= $map['createElement'] ?? null;
                }
            }

            return $maps;
        }

        // multiple maps were provided, so normalize and return each of them
        $maps = [];
        foreach ($map as $m) {
            if (isset($m['map'])) {
                /** @phpstan-ignore-next-line */
                $maps += $this->normalizeEagerLoadingMaps($m);
            }
        }

        return $maps;
    }

    /**
     * @param  array{source:int,target:int,elementType?:class-string<ElementInterface>}[]  $map
     * @return EagerLoadingMap[]
     */
    private function groupMapsByElementType(array $map): array
    {
        if (empty($map)) {
            return [];
        }

        $maps = [];
        $untypedMaps = [];
        $untypedTargetIds = [];

        foreach ($map as $m) {
            if (isset($m['elementType'])) {
                $elementType = $m['elementType'];
                $maps[$elementType] ??= ['elementType' => $elementType];
                $maps[$elementType]['map'][] = $m;
            } else {
                $untypedMaps[] = $m;
                $untypedTargetIds[] = $m['target'];
            }
        }

        if (! empty($untypedMaps)) {
            $elementTypesById = [];

            foreach (array_chunk($untypedTargetIds, 100) as $ids) {
                $types = DB::table(Table::ELEMENTS)
                    ->whereIn('id', $ids)
                    ->pluck('type', 'id');

                // we need to preserve the numeric keys, so array_merge() won't work here
                foreach ($types as $id => $type) {
                    $elementTypesById[$id] = $type;
                }
            }

            foreach ($untypedMaps as $m) {
                if (! isset($elementTypesById[$m['target']])) {
                    continue;
                }
                $elementType = $elementTypesById[$m['target']];
                $maps[$elementType] ??= ['elementType' => $elementType];
                $maps[$elementType]['map'][] = $m;
            }
        }

        return array_values($maps);
    }
}
