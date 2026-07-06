<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for an element index screen (`content/Index`).
 *
 * Query building goes through the shared {@see ElementIndexes} kernel — the
 * same one the legacy XHR endpoints use — while everything page-shaped
 * (view state, pagination, bulk-action items, column/sort metadata, and
 * row/card serialization) lives here.
 *
 * Public methods are payload keys (see {@see ViewModel}); shared intermediates
 * (resolved source, query, index data, paginator) are memoized privately since
 * payload methods may be invoked in any order.
 */
class ContentIndexViewModel extends ViewModel
{
    private const string RENDER_CONTEXT = 'index';

    /** @var array{0: ?string, 1: ?array}|null */
    private ?array $resolvedSource = null;

    private ?ElementQueryInterface $query = null;

    /** @var array<int, array{field: string, direction: string}>|null */
    private ?array $resolvedSort = null;

    private ?array $resolvedViewState = null;

    private ?array $indexData = null;

    private ?LengthAwarePaginator $paginator = null;

    /** @var string[]|null */
    private ?array $visibleColumns = null;

    public function __construct(
        /** @var class-string<ElementInterface> */
        private readonly string $elementType,
        private readonly ElementIndexRequest $request,
        private readonly ?string $sectionHandle = null,
        private readonly array $elementStatuses = [],
    ) {}

    public function status(): string
    {
        return $this->request->input('status', '');
    }

    public function search(): ?string
    {
        return $this->request->input('search');
    }

    public function source(): ?array
    {
        return $this->sourceState()[1];
    }

    /** @return array{id: int, editable: bool}|null */
    public function structure(): ?array
    {
        $indexData = $this->resolveIndexData();

        return isset($indexData['structure'])
            ? ['id' => $indexData['structure']->id, 'editable' => $indexData['structureEditable'] ?? false]
            : null;
    }

    public function currentCondition(): ?array
    {
        return $this->request->condition()?->getConfig();
    }

    public function viewState(): array
    {
        if ($this->resolvedViewState !== null) {
            return $this->resolvedViewState;
        }

        $orderBy = array_values(array_filter(
            $this->sort(),
            fn ($sortItem) => ! empty($sortItem['field']),
        ));

        // The client treats the URL as the source of truth for sorting, so the
        // requested sort maps into order/sort/orderHistory, which indexData()
        // then applies. The resolved visible columns go into `tableColumns` so
        // indexData() prepares (eager-loads) exactly what will render.
        return $this->resolvedViewState = [
            ...$this->request->viewState(),
            'mode' => $this->mode(),
            'tableColumns' => $this->resolveVisibleColumns(),
            'order' => $orderBy[0]['field'] ?? null,
            'sort' => $orderBy[0]['direction'] ?? 'asc',
            'orderHistory' => array_map(
                fn (array $sortItem) => [$sortItem['field'], $sortItem['direction'] ?? 'asc'],
                array_slice($orderBy, 1),
            ),
            'showHeaderColumn' => true,
            'fieldLayouts' => $this->request->fieldLayouts(),
            'returnUrl' => $this->request->returnUrl(),
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function statusOptions(): array
    {
        return collect($this->elementStatuses)
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->prepend(['label' => t('All'), 'value' => ''])
            ->values()
            ->all();
    }

    /**
     * The sortable attributes: the element type's own sort options plus the
     * source's field-layout options. `orderBy` can be a query expression or
     * closure, so only string attributes are addressable from the client.
     *
     * @return array<int, array{label: string, value: string, defaultDir: string}>
     */
    public function sortOptions(): array
    {
        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return [];
        }

        $options = [];

        foreach ($this->elementType::sortOptions() as $attribute => $option) {
            if (! is_array($option)) {
                $options[$attribute] = [
                    'label' => $option,
                    'value' => $attribute,
                    'defaultDir' => 'asc',
                ];

                continue;
            }

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

        foreach (ElementSources::getSourceSortOptions($this->elementType, $sourceKey) as $option) {
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
     * @return array<int, array{label: string, value: string}>
     */
    public function tableColumns(): array
    {
        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return [];
        }

        return ElementSources::getAvailableTableAttributes($this->elementType)
            ->merge(ElementSources::getSourceTableAttributes($this->elementType, $sourceKey))
            ->map(fn (array $attribute, string $key) => [
                'label' => $attribute['label'],
                'value' => $key,
            ])
            ->values()
            ->all();
    }

    /** @return string[] */
    public function defaultTableColumns(): array
    {
        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return [];
        }

        return ElementSources::getTableAttributes(
            elementType: $this->elementType,
            sourceKey: $sourceKey,
            fieldLayouts: $this->request->fieldLayouts(),
        )
            ->map(fn (array $attribute) => $attribute[0])
            ->filter(fn (string $attribute) => $attribute !== 'title')
            ->values()
            ->all();
    }

    public function data(): array
    {
        $elements = $this->resolvePaginator()->items();

        return $this->mode() === 'cards'
            ? $this->cardData($elements)
            : $this->tableRows($elements);
    }

    /** @return array<int, array<string, mixed>>|null */
    public function actions(): ?array
    {
        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return null;
        }

        $availableActions = ElementActions::availableActions($this->elementType, $sourceKey, $this->resolveQuery());

        return empty($availableActions)
            ? null
            : ElementActions::serializeActionItems($availableActions);
    }

    /**
     * The effective sort: a requested sort wins; otherwise the source's
     * configured `defaultSort` (`[attribute, direction]`), then a sensible
     * default.
     *
     * @return array<int, array{field: string, direction: string}>
     */
    public function sort(): array
    {
        if ($this->resolvedSort !== null) {
            return $this->resolvedSort;
        }

        $requestedSort = $this->request->array('sort');

        if (! empty($requestedSort)) {
            return $this->resolvedSort = $requestedSort;
        }

        $defaultSort = $this->sourceState()[1]['defaultSort'] ?? null;

        if (is_array($defaultSort) && isset($defaultSort[0])) {
            return $this->resolvedSort = [[
                'field' => $defaultSort[0],
                'direction' => ($defaultSort[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ]];
        }

        return $this->resolvedSort = [['field' => 'dateCreated', 'direction' => 'desc']];
    }

    public function publishableSections(): array
    {
        return Sections::getPublishableSections();
    }

    /**
     * @return array{
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *     next_page_url: string|null,
     *     prev_page_url: string|null,
     *     from: int|null,
     *     to: int|null,
     * }
     */
    public function pagination(): array
    {
        $paginator = $this->resolvePaginator();

        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /** @return array{0: ?string, 1: ?array} */
    private function sourceState(): array
    {
        if ($this->resolvedSource !== null) {
            return $this->resolvedSource;
        }

        // An explicit ?source= wins; otherwise a section-handle URL (e.g.
        // content/entries/blog, content/entries/singles) selects that source.
        $requestedSource = $this->request->input('source')
            ?? $this->sourceKeyForSectionHandle()
            ?? '*';

        return $this->resolvedSource = app(ElementIndexes::class)
            ->resolveSource($this->elementType, $requestedSource, self::RENDER_CONTEXT);
    }

    /**
     * Maps the section-handle route segment to its source key (`singles` for
     * Single sections, `section:{uid}` otherwise).
     */
    private function sourceKeyForSectionHandle(): ?string
    {
        if ($this->sectionHandle === null || $this->sectionHandle === '') {
            return null;
        }

        if ($this->sectionHandle === 'singles') {
            return 'singles';
        }

        $section = Sections::getSectionByHandle($this->sectionHandle);

        return $section ? "section:$section->uid" : null;
    }

    /**
     * The view mode: sent as a string when the user switches views (see the
     * `useElementIndexViewMode` composable), falling back to the persisted
     * view state, then the default table mode.
     */
    private function mode(): string
    {
        return $this->request->input('viewMode') ?: $this->request->viewState()['mode'];
    }

    /** @return string[] */
    private function resolveVisibleColumns(): array
    {
        if ($this->visibleColumns !== null) {
            return $this->visibleColumns;
        }

        $available = array_column($this->tableColumns(), 'value');

        $requested = array_values(array_unique(array_intersect(
            array_filter($this->request->array('columns'), is_string(...)),
            $available,
        )));

        return $this->visibleColumns = $requested !== []
            ? $requested
            : $this->defaultTableColumns();
    }

    private function resolveQuery(): ElementQueryInterface
    {
        if ($this->query !== null) {
            return $this->query;
        }

        $query = app(ElementIndexes::class)->buildQueryState(
            elementType: $this->elementType,
            source: $this->sourceState()[1],
            condition: $this->request->condition(),
        )['query'];

        $query->status($this->status() ?: null);

        if (($search = $this->search()) !== null && $search !== '') {
            $query->search($search);
        }

        return $this->query = $query;
    }

    /**
     * indexData() applies the ordering and table-attribute preparation to the
     * query and returns the shared index variables (structure info, resolved
     * columns, view flags) — the same data that backs the legacy HTML index —
     * so the two indexes stay in sync.
     */
    private function resolveIndexData(): array
    {
        if ($this->indexData !== null) {
            return $this->indexData;
        }

        $query = $this->resolveQuery();
        $viewState = $this->viewState();

        // Reset any ordering applied while building the query so the requested
        // sort stays authoritative, then let indexData() apply it.
        if ($viewState['order'] !== null) {
            $query->getQuery()->reorder();
        }

        [$sourceKey] = $this->sourceState();

        return $this->indexData = $this->elementType::indexData(
            elementQuery: $query,
            disabledElementIds: $this->request->array('disabledElementIds'),
            viewState: $viewState,
            sourceKey: $sourceKey,
            context: self::RENDER_CONTEXT,
            selectable: true,
            sortable: false,
        );
    }

    /**
     * Paginates a clone of the (ordered, prepared) query. Out-of-range pages
     * clamp to the last valid page.
     */
    private function resolvePaginator(): LengthAwarePaginator
    {
        if ($this->paginator !== null) {
            return $this->paginator;
        }

        // Ordering + attribute prep must be applied before pagination executes.
        $this->resolveIndexData();

        $query = $this->resolveQuery();
        $perPage = max(1, $this->request->integer('per_page', 50));
        $page = max(1, $this->request->integer(Cms::config()->getPageTriggerParam(), 1));
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

        return $this->paginator = $paginator;
    }

    /**
     * Serializes elements as table rows: the title renders as a CpLink-wrapped
     * chip; every other visible column renders through the element's
     * attribute-HTML pipeline (which element types override for attributes
     * like `authors`). Only the visible columns render — the client refetches
     * when its column selection changes.
     *
     * @param  ElementInterface[]  $elements
     */
    private function tableRows(array $elements): array
    {
        $attributes = array_values(array_unique(['title', ...$this->resolveVisibleColumns()]));
        $elementHtml = app(ElementHtml::class);

        return array_map(fn (ElementInterface $element) => [
            'id' => $element->id,
            ...collect($attributes)
                ->mapWithKeys(fn (string $attribute) => [
                    $attribute => $attribute === 'title' ?
                        Html::tag('CpLink',
                            $elementHtml->chipHtml($element, [
                                'context' => self::RENDER_CONTEXT,
                                'appearance' => 'plain',
                            ]),
                            ['href' => $element->getCpEditUrl(), 'inertia' => false]
                        )
                        : (string) $element->getAttributeHtml($attribute),
                ])
                ->all(),
        ], $elements);
    }

    /**
     * Serializes elements as server-rendered card parts for the cards view.
     * Vue owns the selection process, so cards render non-selectable.
     *
     * @param  ElementInterface[]  $elements
     */
    private function cardData(array $elements): array
    {
        $elementHtml = app(ElementHtml::class);

        return array_map(function (ElementInterface $element) use ($elementHtml): array {
            // A per-element `id` is shared across the full card and its parts
            // so the header/body/footer line up if they're recomposed
            // client-side, while staying unique per card.
            $cardConfig = [
                'id' => sprintf('card-%s', mt_rand()),
                'context' => self::RENDER_CONTEXT,
                'hyperlink' => true,
                'showEditButton' => false,
                'autoReload' => false,
                'selectable' => false,
                'sortable' => false,
            ];

            return [
                'id' => $element->id,
                'cardAttributes' => $elementHtml->elementCardAttributes($element, $cardConfig),
                'cardHeaderHtml' => $elementHtml->elementCardHeaderHtml($element, $cardConfig),
                'cardContentHtml' => $elementHtml->elementCardContentHtml($element, $cardConfig),
                'cardFooterHtml' => $elementHtml->elementCardFooterHtml($element, $cardConfig),
            ];
        }, $elements);
    }
}
