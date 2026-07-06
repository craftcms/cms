<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Assembles the data behind Inertia element index screens: query building,
 * view state, pagination, bulk-action items, column/sort option metadata, and
 * table-row/card serialization. Controllers stay thin request/response wiring.
 */
#[Scoped]
class ElementIndexes
{
    use InteractsWithElementIndexes;

    public function __construct(
        private readonly ElementSources $elementSources,
        private readonly ElementHtml $elementHtml,
        private readonly ElementActions $elementActions,
    ) {}

    /**
     * Resolves the effective sort: a requested sort wins; otherwise the
     * source's configured `defaultSort` (`[attribute, direction]`), then a
     * sensible default.
     *
     * @return array<int, array{field: string, direction: string}>
     */
    public function resolveSort(array $requestedSort, ?array $source): array
    {
        if (! empty($requestedSort)) {
            return $requestedSort;
        }

        $defaultSort = $source['defaultSort'] ?? null;

        if (is_array($defaultSort) && isset($defaultSort[0])) {
            return [[
                'field' => $defaultSort[0],
                'direction' => ($defaultSort[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ]];
        }

        return [['field' => 'dateCreated', 'direction' => 'desc']];
    }

    /**
     * Builds the element query for a source, with the current condition,
     * status, and search applied.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function buildQuery(
        string $elementType,
        ?array $source,
        ?ElementConditionInterface $condition = null,
        ?string $status = null,
        ?string $search = null,
    ): ElementQueryInterface {
        $query = $this->buildElementQueryState($elementType, $source, $condition)['query'];

        $query->status($status ?: null);

        if ($search !== null && $search !== '') {
            $query->search($search);
        }

        return $query;
    }

    /**
     * Assembles the view state for the index. The client treats the URL as the
     * source of truth for sorting, so the requested sort maps into the view
     * state's order/sort/orderHistory, which indexData() then applies. The
     * resolved visible columns go into `tableColumns` so indexData() prepares
     * (eager-loads) exactly the attributes that will be rendered.
     *
     * @param  array<int, array{field: string, direction: string}>  $sort
     * @param  string[]|null  $tableColumns
     */
    public function viewState(array $sort, string $mode, ?array $tableColumns = null): array
    {
        $orderBy = array_values(array_filter(
            $sort,
            fn ($sortItem) => ! empty($sortItem['field']),
        ));

        return [
            ...$this->resolveViewState(),
            'mode' => $mode,
            'tableColumns' => $tableColumns,
            'order' => $orderBy[0]['field'] ?? null,
            'sort' => $orderBy[0]['direction'] ?? 'asc',
            'orderHistory' => array_map(
                fn (array $sortItem) => [$sortItem['field'], $sortItem['direction'] ?? 'asc'],
                array_slice($orderBy, 1),
            ),
            'showHeaderColumn' => true,
            'fieldLayouts' => $this->resolveFieldLayouts(),
            'returnUrl' => $this->resolveReturnUrl(),
        ];
    }

    /**
     * Paginates the query. Out-of-range pages clamp to the last valid page.
     *
     * @return array{0: LengthAwarePaginator, 1: array}
     */
    public function paginate(ElementQueryInterface $query, int $perPage, int $page): array
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $pageParam = Cms::config()->getPageTriggerParam();

        $paginator = (clone $query)->paginate(
            perPage: $perPage,
            pageName: $pageParam,
            page: $page,
        );

        if ($page > $paginator->lastPage()) {
            $paginator = (clone $query)->paginate(
                perPage: $perPage,
                pageName: $pageParam,
                page: max(1, $paginator->lastPage()),
            );
        }

        return [$paginator, [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]];
    }

    /**
     * Serializes the available bulk actions for the source so the bulk-actions
     * bar can offer them, or null when the source is unresolved or has none.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function actionItems(string $elementType, ?string $sourceKey, ElementQueryInterface $query): ?array
    {
        if ($sourceKey === null) {
            return null;
        }

        $availableActions = $this->elementActions->availableActions($elementType, $sourceKey, $query);

        return empty($availableActions)
            ? null
            : $this->elementActions->serializeActionItems($availableActions);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array<int, array{label: string, value: string, defaultDir: string}>
     */
    public function sortOptions(string $elementType, ?string $sourceKey): array
    {
        if ($sourceKey === null) {
            return [];
        }

        $options = [];

        // The element type's own sort options: either `attribute => label`
        // pairs or arrays with label/attribute/orderBy/defaultDir keys.
        foreach ($elementType::sortOptions() as $attribute => $option) {
            if (! is_array($option)) {
                $options[$attribute] = [
                    'label' => $option,
                    'value' => $attribute,
                    'defaultDir' => 'asc',
                ];

                continue;
            }

            // `orderBy` can be a query expression or closure; only string
            // attributes are addressable from the client.
            $value = $option['attribute']
                ?? (is_string($option['orderBy'] ?? null) ? $option['orderBy'] : null);

            if (is_string($value) && $value !== '') {
                $options[$value] = [
                    'label' => $option['label'] ?? $value,
                    'value' => $value,
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ];
            }
        }

        // Plus the source's field-layout sort options (sortable custom fields).
        foreach ($this->elementSources->getSourceSortOptions($elementType, $sourceKey) as $option) {
            $value = $option['attribute']
                ?? (is_string($option['orderBy'] ?? null) ? $option['orderBy'] : null);

            if (is_string($value) && $value !== '' && ! isset($options[$value])) {
                $options[$value] = [
                    'label' => $option['label'] ?? $value,
                    'value' => $value,
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ];
            }
        }

        return array_values($options);
    }

    /**
     * Selectable columns: common attributes plus the source's field columns.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return Collection<int, array{label: string, value: string}>
     */
    public function availableTableColumns(string $elementType, ?string $sourceKey): Collection
    {
        if ($sourceKey === null) {
            return collect();
        }

        return $this->elementSources->getAvailableTableAttributes($elementType)
            ->merge($this->elementSources->getSourceTableAttributes($elementType, $sourceKey))
            ->map(fn (array $attribute, string $key) => [
                'label' => $attribute['label'],
                'value' => $key,
            ])
            ->values();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return string[]
     */
    public function defaultTableColumns(string $elementType, ?string $sourceKey, ?array $fieldLayouts): array
    {
        if ($sourceKey === null) {
            return [];
        }

        return $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            fieldLayouts: $fieldLayouts,
        )
            ->map(fn (array $attribute) => $attribute[0])
            ->filter(fn (string $attribute) => $attribute !== 'title')
            ->values()
            ->all();
    }

    /**
     * Resolves the columns to render: the client's requested columns (its
     * per-source visibility selection), validated against what the source
     * offers, falling back to the source/element-type defaults.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  string[]  $requested
     * @return string[]
     */
    public function visibleTableColumns(
        string $elementType,
        ?string $sourceKey,
        array $requested,
        ?array $fieldLayouts = null,
    ): array {
        $available = $this->availableTableColumns($elementType, $sourceKey)
            ->pluck('value')
            ->all();

        $requested = array_values(array_unique(array_intersect(
            array_filter($requested, is_string(...)),
            $available,
        )));

        return $requested !== []
            ? $requested
            : $this->defaultTableColumns($elementType, $sourceKey, $fieldLayouts);
    }

    /**
     * Serializes elements as table rows: the title renders as a CpLink-wrapped
     * chip; every other visible column renders through the element's
     * attribute-HTML pipeline (which element types override for attributes
     * like `authors`). Only the given columns render — the client refetches
     * when its column selection changes.
     *
     * @param  iterable<ElementInterface>  $elements
     * @param  class-string<ElementInterface>  $elementType
     * @param  string[]  $columns
     */
    public function tableRows(iterable $elements, string $elementType, array $columns, string $context): Collection
    {
        $attributes = array_values(array_unique(['title', ...$columns]));

        return collect($elements)
            ->map(fn (ElementInterface $element) => [
                'id' => $element->id,
                ...collect($attributes)
                    ->mapWithKeys(fn (string $attribute) => [
                        $attribute => $attribute === 'title' ?
                            Html::tag('CpLink',
                                $this->elementHtml->chipHtml($element, [
                                    'context' => $context,
                                    'appearance' => 'plain',
                                ]),
                                ['href' => $element->getCpEditUrl(), 'inertia' => false]
                            )
                            : (string) $element->getAttributeHtml($attribute),
                    ])
                    ->all(),
            ]);
    }

    /**
     * Serializes elements as server-rendered card parts for the cards view.
     * Vue owns the selection process, so cards render non-selectable.
     *
     * @param  iterable<ElementInterface>  $elements
     */
    public function cardData(iterable $elements, string $context): Collection
    {
        return collect($elements)
            ->map(function (ElementInterface $element) use ($context) {
                // A per-element `id` is shared across the full card and its
                // parts so the header/body/footer line up if they're
                // recomposed client-side, while staying unique per card.
                $cardConfig = [
                    'id' => sprintf('card-%s', mt_rand()),
                    'context' => $context,
                    'hyperlink' => true,
                    'showEditButton' => false,
                    'autoReload' => false,
                    'selectable' => false,
                    'sortable' => false,
                ];

                return [
                    'id' => $element->id,
                    'cardAttributes' => $this->elementHtml->elementCardAttributes($element, $cardConfig),
                    'cardHeaderHtml' => $this->elementHtml->elementCardHeaderHtml($element, $cardConfig),
                    'cardContentHtml' => $this->elementHtml->elementCardContentHtml($element, $cardConfig),
                    'cardFooterHtml' => $this->elementHtml->elementCardFooterHtml($element, $cardConfig),
                ];
            });
    }
}
