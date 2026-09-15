<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Container\Attributes\Scoped;

use function CraftCms\Cms\t;

/**
 * The element index kernel: source resolution and element-query-state building
 * shared by every index surface — the Inertia screen's
 * {@see ContentIndexViewModel}, the legacy
 * XHR endpoints, and {@see ElementIndexResource}.
 * Page-payload assembly lives in the view model; legacy HTML formatting in the
 * resource.
 */
#[Scoped]
class ElementIndexes
{
    public function __construct(
        private readonly ElementSources $elementSources,
    ) {}

    /**
     * Resolves a source key to its source config for the given context.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array{0:?string,1:?array<string,mixed>}
     */
    public function resolveSource(string $elementType, ?string $sourceKey, string $context): array
    {
        if (! isset($sourceKey)) {
            return [$sourceKey, null];
        }

        if ($sourceKey === '__IMP__') {
            return [$sourceKey, [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '__IMP__',
                'label' => t('All elements'),
                'hasThumbs' => $elementType::hasThumbs(),
            ]];
        }

        $source = $this->elementSources->findSource($elementType, $sourceKey, $context);

        if ($source === null) {
            $sourceKey = null;
        }

        return [$sourceKey, $source];
    }

    /**
     * Builds the element query state for a source, applying the source's own
     * condition/criteria plus any client-supplied condition, criteria, filter
     * condition, and collapsed-element exclusions.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string,mixed>|null  $source
     * @param  array<string,mixed>  $baseCriteria
     * @param  array<string,mixed>  $criteria
     * @param  array<string,mixed>|null  $filterConditionConfig
     * @param  int[]  $collapsedElementIds
     * @return array{query: ElementQueryInterface, unfilteredQuery: ElementQueryInterface|null}
     */
    public function buildQueryState(
        string $elementType,
        ?array $source,
        ?ElementConditionInterface $condition = null,
        array $baseCriteria = [],
        array $criteria = [],
        ?array $filterConditionConfig = null,
        array $collapsedElementIds = [],
    ): array {
        $query = $elementType::find();

        if (! $source) {
            $query->id(false);

            return [
                'query' => $query,
                'unfilteredQuery' => null,
            ];
        }

        $applyCriteria = function (array $criteria) use ($query): bool {
            if (! $criteria) {
                return false;
            }

            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = filter_var($criteria['trashed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['drafts'])) {
                $criteria['drafts'] = filter_var($criteria['drafts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['draftOf'])) {
                if (is_numeric($criteria['draftOf']) && $criteria['draftOf'] != 0) {
                    $criteria['draftOf'] = (int) $criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }

            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($criteria));

            return true;
        };

        if ($source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($source['condition']);
            $sourceCondition->forQuery = true;
            $sourceCondition->modifyQuery($query);
        } else {
            $applyCriteria($source['criteria'] ?? []);
        }

        $applyCriteria($baseCriteria);

        $unfilteredQuery = clone $query;
        $hasFilters = false;

        if ($condition) {
            $condition->modifyQuery($query);

            $hasFilters = true;
        }

        if ($applyCriteria($criteria)) {
            $hasFilters = true;
        }

        if ($filterConditionConfig) {
            /** @var ElementConditionInterface $filterCondition */
            $filterCondition = Conditions::createCondition($filterConditionConfig);
            $filterCondition->forQuery = true;
            $filterCondition->modifyQuery($query);

            $hasFilters = true;
        }

        if (! $collapsedElementIds) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $descendantQuery = (clone $query)
            ->offset(null)
            ->limit(null)
            ->reorder()
            ->positionedAfter(null)
            ->positionedBefore(null)
            ->status(null);

        $collapsedElements = (clone $descendantQuery)
            ->id($collapsedElementIds)
            ->orderBy('lft')
            ->all();

        if (empty($collapsedElements)) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $descendantIds = [];

        foreach ($collapsedElements as $element) {
            if (in_array($element->id, $descendantIds, false)) {
                continue;
            }

            $elementDescendantIds = (clone $descendantQuery)
                ->descendantOf($element)
                ->ids();

            $descendantIds = array_merge($descendantIds, $elementDescendantIds);
        }

        if (empty($descendantIds)) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $query->where(new ExcludeDescendantIdsExpression($descendantIds));

        return [
            'query' => $query,
            'unfilteredQuery' => $unfilteredQuery,
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array<array-key,mixed>|null
     */
    public function availableExporters(string $elementType, string $sourceKey, bool $mobileBrowser = false): ?array
    {
        if ($mobileBrowser) {
            return null;
        }

        return ElementExporters::availableExporters($elementType, $sourceKey);
    }
}
