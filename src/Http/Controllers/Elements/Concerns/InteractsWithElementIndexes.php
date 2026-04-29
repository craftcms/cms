<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ElementSources as ElementSourcesFacade;
use CraftCms\Cms\Support\Typecast;

use function CraftCms\Cms\t;

trait InteractsWithElementIndexes
{
    protected function resolveElementIndexCondition(): ?ElementConditionInterface
    {
        /** @var array|null $conditionConfig */
        /** @phpstan-var array{class:class-string<ElementConditionInterface>}|null $conditionConfig */
        $conditionConfig = request()->input('condition');

        if (! $conditionConfig) {
            return null;
        }

        /** @var ElementConditionInterface $condition */
        $condition = Conditions::createCondition($conditionConfig);

        if ($condition instanceof ElementCondition) {
            $referenceElementId = request()->input('referenceElementId');

            if ($referenceElementId) {
                $criteria = [];

                if ($ownerId = request()->input('referenceElementOwnerId')) {
                    $criteria['ownerId'] = $ownerId;
                }

                $condition->referenceElement = Elements::getElementById(
                    (int) $referenceElementId,
                    siteId: request()->input('referenceElementSiteId'),
                    criteria: $criteria,
                );
            }
        }

        return $condition;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array{0:?string,1:?array}
     */
    protected function resolveSource(string $elementType, ?string $sourceKey, string $context): array
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

        $source = ElementSourcesFacade::findSource($elementType, $sourceKey, $context);

        if ($source === null) {
            $sourceKey = null;
        }

        return [$sourceKey, $source];
    }

    /**
     * @return FieldLayout[]|null
     */
    protected function resolveFieldLayouts(): ?array
    {
        $fieldLayouts = request()->input('fieldLayouts');

        if (empty($fieldLayouts) || ! is_array($fieldLayouts)) {
            return null;
        }

        return array_map(
            FieldLayout::createFromConfig(...),
            $fieldLayouts,
        );
    }

    protected function resolveViewState(): array
    {
        $viewState = request()->input('viewState', []);

        if (empty($viewState['mode'])) {
            $viewState['mode'] = 'table';
        }

        return $viewState;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array{query:ElementQueryInterface,unfilteredQuery:ElementQueryInterface|null}
     */
    protected function buildElementQueryState(
        string $elementType,
        ?array $source,
        ?ElementConditionInterface $condition,
    ): array {
        $query = $elementType::find();

        if (! $source) {
            $query->id(false);

            return [
                'query' => $query,
                'unfilteredQuery' => null,
            ];
        }

        if ($source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($source['condition']);
            $sourceCondition->modifyQuery($query);
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

        $applyCriteria(request()->input('baseCriteria') ?? []);

        $unfilteredQuery = clone $query;
        $hasFilters = false;

        if ($condition) {
            $condition->modifyQuery($query);

            $hasFilters = true;
        }

        if ($applyCriteria(request()->input('criteria') ?? [])) {
            $hasFilters = true;
        }

        $filterConditionConfig = request()->input('filterConfig');

        if (! $filterConditionConfig && $filterConditionStr = request()->input('filters')) {
            parse_str((string) $filterConditionStr, $filterConditionConfig);
            $filterConditionConfig = $filterConditionConfig['condition'] ?? null;
        }

        if ($filterConditionConfig) {
            /** @var ElementConditionInterface $filterCondition */
            $filterCondition = Conditions::createCondition($filterConditionConfig);
            $filterCondition->modifyQuery($query);

            $hasFilters = true;
        }

        $collapsedElementIds = request()->input('collapsedElementIds');

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
     */
    protected function availableExporters(string $elementType, string $sourceKey): ?array
    {
        if (request()->isMobileBrowser()) {
            return null;
        }

        return ElementExporters::availableExporters($elementType, $sourceKey);
    }

    protected function populateFilterHudQueryParams(
        ElementConditionInterface $condition,
        ?array $source,
        ?string $sourceKey,
        ?ElementConditionInterface $currentCondition,
    ): void {
        if ($source !== null) {
            if ($source['type'] === ElementSources::TYPE_NATIVE) {
                $condition->queryParams = array_keys($source['criteria'] ?? []);
                $condition->sourceKey = $sourceKey;
            } else {
                /** @var ElementConditionInterface $sourceCondition */
                $sourceCondition = Conditions::createCondition($source['condition']);
                $condition->queryParams = [];

                foreach ($sourceCondition->getConditionRules() as $rule) {
                    /** @var ElementConditionRuleInterface $rule */
                    array_push($condition->queryParams, ...$rule->getExclusiveQueryParams());
                }
            }
        }

        if ($currentCondition) {
            foreach ($currentCondition->getConditionRules() as $rule) {
                /** @var ElementConditionRuleInterface $rule */
                array_push($condition->queryParams, ...$rule->getExclusiveQueryParams());
            }
        }

        $condition->queryParams[] = 'site';
        $condition->queryParams[] = 'status';
        $condition->queryParams = array_values(array_unique($condition->queryParams));
    }
}
