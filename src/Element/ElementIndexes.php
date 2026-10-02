<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Database\Query\Expression as ExpressionInterface;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

use function CraftCms\Cms\t;

/**
 * The element index kernel: source resolution and element-query-state building
 * shared by every index surface — the Inertia screen's
 * {@see ContentIndexViewModel}, the legacy
 * XHR endpoints, and {@see ElementIndexResource}.
 * Page-payload assembly lives in the view model; legacy HTML formatting in the
 * resource.
 *
 * @since 6.0.0
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

        // Compared against a literal rather than passed alone: a single-argument
        // where() treats the expression as a column name and routes it to
        // whereNull(), compiling to `<expr> is null` — which matches nothing, so
        // collapsing a branch emptied the whole index. The comparison keeps the
        // expression in the where's `column` slot, which
        // {@see DisplayedInIndex::elementQueryWithAllDescendants()} looks for
        // when it strips the exclusion back off.
        $query->where(new ExcludeDescendantIdsExpression($descendantIds), '=', DB::raw('true'));

        return [
            'query' => $query,
            'unfilteredQuery' => $unfilteredQuery,
        ];
    }

    /**
     * Applies client-addressable element index sorting to a query.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  iterable<array{field:string,direction:string}>  $sort
     * @param  array<string,mixed>|null  $source
     */
    public function applySort(
        string $elementType,
        ElementQueryInterface $elementQuery,
        string $sourceKey,
        iterable $sort,
        bool $reset = false,
        ?array $source = null,
    ): void {
        $primary = true;

        foreach ($sort as $item) {
            if ($primary && $item['field'] === 'structure') {
                if (! isset($source['structureId'])) {
                    return;
                }

                if ($reset) {
                    $elementQuery->getQuery()->reorder();
                }

                $elementQuery
                    ->structureId($source['structureId'])
                    ->orderBy('lft');

                return;
            }

            $orderBy = $this->indexOrderBy($elementType, $sourceKey, $item['field'], $item['direction']);

            if (! $orderBy) {
                return;
            }

            if ($primary && $reset) {
                $elementQuery->getQuery()->reorder();
            }

            $this->applyOrderBy($elementQuery, $orderBy);

            if ($primary && is_array($orderBy) && isset($orderBy['score'])) {
                return;
            }

            $primary = false;
        }
    }

    /** @param array<array-key,mixed>|ExpressionInterface $orderBy */
    private function applyOrderBy(ElementQueryInterface $elementQuery, ExpressionInterface|array $orderBy): void
    {
        foreach (Arr::wrap($orderBy) as $column => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $elementQuery->getQuery()->orderByRaw(
                    $direction->getValue(DB::getQueryGrammar()),
                );

                continue;
            }

            $elementQuery->getQuery()->orderBy($column, match ($direction) {
                'desc', SORT_DESC => 'desc',
                default => 'asc',
            });
        }
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ExpressionInterface|array<array-key,mixed>|false
     */
    private function indexOrderBy(
        string $elementType,
        string $sourceKey,
        string $attribute,
        string $direction,
    ): ExpressionInterface|array|false {
        $sortDirection = strcasecmp($direction, 'desc') === 0 ? SORT_DESC : SORT_ASC;
        $columns = $this->indexOrderByColumns($elementType, $sourceKey, $attribute, $sortDirection);

        if ($columns === false || $columns instanceof ExpressionInterface) {
            return $columns;
        }

        $columns = is_string($columns)
            ? preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY)
            : $columns;

        return $this->normalizeOrderByColumns($columns, $sortDirection);
    }

    /**
     * @param  array<array-key,mixed>  $columns
     * @return array<array-key,int>
     */
    private function normalizeOrderByColumns(array $columns, int $defaultDirection): array
    {
        $result = [];

        foreach ($columns as $i => $column) {
            if ($i === 0) {
                $result[$column] = $defaultDirection;

                continue;
            }

            if (preg_match('/^(.*?)\s+(asc|desc)$/i', (string) $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') === 0 ? SORT_DESC : SORT_ASC;

                continue;
            }

            $result[$column] = SORT_ASC;
        }

        return $result;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ExpressionInterface|bool|array<array-key,mixed>|string
     */
    private function indexOrderByColumns(
        string $elementType,
        string $sourceKey,
        string $attribute,
        int $direction,
    ): ExpressionInterface|bool|array|string {
        if (! $attribute) {
            return false;
        }

        if ($attribute === 'score') {
            return 'score';
        }

        $orderBy = $this->resolveSortOption($elementType, $attribute, $direction);

        return $orderBy ?: $this->resolveSourceSortOption($elementType, $sourceKey, $attribute, $direction);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ExpressionInterface|array<array-key,mixed>|string|false
     */
    private function resolveSortOption(string $elementType, string $attribute, int $direction): ExpressionInterface|array|string|false
    {
        foreach ($elementType::sortOptions() as $key => $sortOption) {
            if (! is_array($sortOption) && $key === $attribute) {
                return $key;
            }

            if (is_array($sortOption)) {
                $optionAttribute = $sortOption['attribute'] ?? $sortOption['orderBy'];
                if ($optionAttribute === $attribute) {
                    return is_callable($sortOption['orderBy'])
                        ? $sortOption['orderBy']($direction)
                        : $sortOption['orderBy'];
                }
            }
        }

        return false;
    }

    /** @param class-string<ElementInterface> $elementType */
    private function resolveSourceSortOption(
        string $elementType,
        string $sourceKey,
        string $attribute,
        int $direction,
    ): ExpressionInterface|bool {
        $sourceSortOptions = $this->elementSources->getSourceSortOptions($elementType, $sourceKey);

        foreach ($sourceSortOptions as $sortOption) {
            if ($sortOption['attribute'] !== $attribute) {
                continue;
            }

            $orderBy = $sortOption['orderBy'];

            if ($orderBy instanceof Coalesce) {
                $sql = $orderBy->getValue(DB::getQueryGrammar());
            } elseif (is_string($orderBy)) {
                $sql = $orderBy;
            } else {
                return $orderBy;
            }

            $sqlDirection = $direction === SORT_ASC ? 'ASC' : 'DESC';

            return DB::raw("$sql $sqlDirection");
        }

        return false;
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
