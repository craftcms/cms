<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use craft\base\ElementInterface;
use craft\db\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ElementSources as ElementSourcesFacade;
use CraftCms\Cms\Support\Typecast;

use function CraftCms\Cms\t;

trait InteractsWithElementIndexes
{
    protected function condition(): ?ElementConditionInterface
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
    protected function source(string $elementType, ?string $sourceKey, string $context): array
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

    protected function viewState(): array
    {
        $viewState = request()->input('viewState', []);

        if (empty($viewState['mode'])) {
            $viewState['mode'] = 'table';
        }

        return $viewState;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    protected function elementQuery(
        string $elementType,
        ?array $source,
        ?ElementConditionInterface $condition,
    ): ElementQueryInterface {
        $query = $elementType::find();

        if (! $source) {
            $query->id(false);

            return $query;
        }

        if ($source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($source['condition']);
            $sourceCondition->modifyQuery($query);
        }

        $applyCriteria = function (array $criteria) use ($query): void {
            if (! $criteria) {
                return;
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
        };

        $applyCriteria(request()->input('baseCriteria') ?? []);

        if ($condition) {
            $condition->modifyQuery($query);
        }

        $applyCriteria(request()->input('criteria') ?? []);

        $filterConditionConfig = request()->input('filterConfig');

        if (! $filterConditionConfig && $filterConditionStr = request()->input('filters')) {
            parse_str((string) $filterConditionStr, $filterConditionConfig);
            $filterConditionConfig = $filterConditionConfig['condition'] ?? null;
        }

        if ($filterConditionConfig) {
            /** @var ElementConditionInterface $filterCondition */
            $filterCondition = Conditions::createCondition($filterConditionConfig);
            $filterCondition->modifyQuery($query);
        }

        $collapsedElementIds = request()->input('collapsedElementIds');

        if (! $collapsedElementIds) {
            return $query;
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
            return $query;
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
            return $query;
        }

        return $query instanceof ElementQuery
            ? $query->whereNotIn('elements.id', $descendantIds)
            : $query->andWhere(new ExcludeDescendantIdsExpression($descendantIds));
    }

    protected function isAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index'], true);
    }
}
