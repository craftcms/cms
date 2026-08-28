<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as IlluminatePaginator;

use function CraftCms\Cms\t;
use function Termwind\render;

/**
 * The shared Inertia payload for an element index screen.
 *
 * Query building goes through the shared {@see ElementIndexes} kernel — the
 * same one the legacy XHR endpoints use — while everything page-shaped
 * (view state, pagination, bulk-action items, column/sort metadata, and
 * row/card serialization) lives here.
 *
 * Element-type view models extend this with their own payload keys (public
 * methods) and can supply a default source via {@see defaultSourceKey()} —
 * e.g. entries map a section-handle URL segment, assets a volume path.
 *
 * Public methods are payload keys (see {@see ViewModel}); shared intermediates
 * (resolved source, query, index data, paginator) are memoized privately since
 * payload methods may be invoked in any order.
 */
abstract class ContentIndexViewModel extends ViewModel
{
    protected const string RENDER_CONTEXT = 'index';

    /** The thumbnail edge length (px) requested for the thumbnail grid view. */
    private const int THUMB_SIZE = 200;

    /** @var array{0: ?string, 1: ?array<string, mixed>}|null */
    private ?array $resolvedSource = null;

    private ?ElementQueryInterface $query = null;

    /** @var array<int, array{field: string, direction: string}>|null */
    private ?array $resolvedSort = null;

    /** @var array<string, mixed>|null */
    private ?array $resolvedViewState = null;

    /** @var array<string, mixed>|null */
    private ?array $indexData = null;

    /** @var LengthAwarePaginator<array-key, ElementInterface|array<string, mixed>>|null */
    private ?LengthAwarePaginator $paginator = null;

    /** @var array{perPage: int, page: int, total: int, pageParam: string}|null */
    private ?array $paginationState = null;

    /** @var string[]|null */
    private ?array $visibleColumns = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $resolvedSources = null;

    public function __construct(
        /** @var class-string<ElementInterface> */
        protected readonly string $elementType,
        protected readonly ElementIndexRequest $request,
        protected readonly ?string $page = null,
    ) {}

    /**
     * The source key to fall back to when the request doesn't name one —
     * how a type-specific URL (section handle, volume path, …) selects its
     * source. `null` falls through to the “all elements” source.
     */
    protected function defaultSourceKey(): ?string
    {
        return null;
    }

    /**
     * The render context — index screens always render in the `index`
     * context, and the client echoes it back on XHR element endpoints.
     */
    public function context(): string
    {
        return static::RENDER_CONTEXT;
    }

    public function status(): string
    {
        return $this->request->input('status', '') ?? '';
    }

    public function search(): ?string
    {
        return $this->request->input('search');
    }

    /** @return array<string, mixed>|null */
    public function source(): ?array
    {
        return $this->sourceState()[1];
    }

    /** @return array{id: int, editable: bool}|null */
    public function structure(): ?array
    {
        if ($this->sourceState()[0] === null) {
            return null;
        }

        $indexData = $this->resolveIndexData();

        return isset($indexData['structure'])
            ? ['id' => $indexData['structure']->id, 'editable' => $indexData['structureEditable'] ?? false]
            : null;
    }

    /** @return array<string, mixed>|null */
    public function currentCondition(): ?array
    {
        return $this->request->condition()?->getConfig();
    }

    /** @return array<string, mixed> */
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
        // Status filtering is a no-op for element types without statuses
        // (QueriesStatuses ignores it), so don't offer the filter at all.
        if (! $this->showStatusMenu()) {
            return [];
        }

        return collect($this->elementType::statuses())
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->prepend(['label' => t('All'), 'value' => ''])
            ->values()
            ->all();
    }

    public function showStatusMenu(): bool
    {
        return $this->indexState()->showStatusMenu($this->elementType);
    }

    public function showSiteMenu(): bool
    {
        // The shared resolution is just "is the element type localized?"; the
        // Inertia index additionally only ever offers the menu on multi-site
        // installs, where there's something to switch between.
        return Sites::isMultiSite() && $this->indexState()->showSiteMenu($this->elementType);
    }

    /**
     * The element index page title: a custom index page's own name wins,
     * otherwise the element type's plural display name.
     */
    public function title(): string
    {
        if ($this->page !== null) {
            $pageName = $this->sources()[0]['page'] ?? null;

            if ($pageName !== null) {
                return t($pageName, category: 'site');
            }
        }

        return $this->elementType::pluralDisplayName();
    }

    /** @return list<array<string, mixed>> */
    public function sources(): array
    {
        // The context is deliberately left at ElementSources' `index` default,
        // which is what this method has always resolved under. (sourceState()
        // passes static::RENDER_CONTEXT explicitly — the same value today.)
        return $this->resolvedSources ??= $this->indexState()->sources(
            $this->elementType,
            withDisabled: true,
            page: $this->page,
        )->all();
    }

    /** @return class-string<ElementInterface> */
    public function elementType(): string
    {
        return $this->elementType;
    }

    public function page(): ?string
    {
        return $this->page;
    }

    public function elementDisplayName(): string
    {
        return $this->elementType::displayName();
    }

    public function elementPluralDisplayName(): string
    {
        return $this->elementType::pluralDisplayName();
    }

    public function canHaveDrafts(): bool
    {
        return $this->elementType::hasDrafts();
    }

    /** @return array<array-key, mixed> */
    public function viewModes(): array
    {
        return $this->elementType::indexViewModes();
    }

    public function selectedSubnavItem(): ?string
    {
        return $this->page !== null
            ? ElementSources::pageNameId($this->page)
            : null;
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

        $indexState = $this->indexState();
        $options = [];

        foreach ($indexState->sortOptions($this->elementType) as $option) {
            $value = self::addressableSortAttribute($option);

            if ($value !== null) {
                $options[$value] = [
                    'label' => $option['label'] ?? $value,
                    'value' => $value,
                    'defaultDir' => $option['defaultDir'],
                ];
            }
        }

        // The element type's own options win over a source's field-layout
        // options that happen to sort on the same attribute.
        foreach (ElementSources::getSourceSortOptions($this->elementType, $sourceKey) as $key => $option) {
            $option = $indexState->normalizeSortOption($option, $key);
            $value = self::addressableSortAttribute($option);

            if ($value !== null && ! isset($options[$value])) {
                $options[$value] = [
                    'label' => $option['label'] ?? $value,
                    'value' => $value,
                    'defaultDir' => $option['defaultDir'],
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

        return $this->indexState()
            ->tableColumns($this->elementType, $sourceKey)
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

    /** @return list<array<string, mixed>> */
    public function data(): array
    {
        if ($this->sourceState()[0] === null) {
            return [];
        }

        $elements = $this->resolvePaginator()->items();

        return match ($this->mode()) {
            ElementIndexViewMode::Cards->value => $this->cardData($elements),
            ElementIndexViewMode::Thumbs->value => $this->thumbData($elements),
            default => $this->tableRows($elements),
        };
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
            return $this->resolvedSort = [
                [
                    'field' => $defaultSort[0],
                    'direction' => ($defaultSort[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
                ],
            ];
        }

        return $this->resolvedSort = [['field' => 'dateCreated', 'direction' => 'desc']];
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
        if ($this->sourceState()[0] === null) {
            return [
                'total' => 0,
                'per_page' => max(1, $this->request->integer('per_page', 50)),
                'current_page' => 1,
                'last_page' => 1,
                'next_page_url' => null,
                'prev_page_url' => null,
                'from' => null,
                'to' => null,
            ];
        }

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

    /** @return array{0: ?string, 1: ?array<string, mixed>} */
    protected function sourceState(): array
    {
        if ($this->resolvedSource !== null) {
            return $this->resolvedSource;
        }

        // An explicit ?source= wins; otherwise the element type's default
        // (e.g. a section-handle or volume-path URL) selects its source.
        $requestedSource = $this->request->input('source')
            ?? $this->defaultSourceKey()
            ?? '*';

        $resolved = app(ElementIndexes::class)
            ->resolveSource($this->elementType, $requestedSource, static::RENDER_CONTEXT);

        // Not every element type has a `*` source (assets index per-volume,
        // for example), so mirror the legacy index's behavior and fall back
        // to the first available source.
        if ($resolved[0] === null) {
            $firstSourceKey = ElementSources::getSources($this->elementType, static::RENDER_CONTEXT)
                ->first(fn (array $source): bool => isset($source['key']))['key'] ?? null;

            if ($firstSourceKey !== null && $firstSourceKey !== $requestedSource) {
                $resolved = app(ElementIndexes::class)
                    ->resolveSource($this->elementType, $firstSourceKey, static::RENDER_CONTEXT);
            }
        }

        return $this->resolvedSource = $resolved;
    }

    /**
     * The shared server-side index state — source, column, sort-option and
     * menu resolution, shared with the server-rendered index shell.
     */
    protected function indexState(): ElementIndexState
    {
        return app(ElementIndexState::class);
    }

    /**
     * The client addresses sort options by attribute name, so options that only
     * sort by a query expression or closure aren't offered.
     *
     * @param  array{attribute: mixed, ...}  $option
     */
    private static function addressableSortAttribute(array $option): ?string
    {
        $attribute = $option['attribute'];

        return is_string($attribute) && $attribute !== '' ? $attribute : null;
    }

    /**
     * The view mode: sent as a string when the user switches views (see the
     * `useElementIndexViewMode` composable), falling back to the persisted
     * view state, then the default table mode.
     *
     * @TODO this should maybe return the ElementIndexViewMode enum?
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
    /** @return array<string, mixed> */
    private function resolveIndexData(): array
    {
        if ($this->indexData !== null) {
            return $this->indexData;
        }

        $query = $this->resolveQuery();
        $viewState = $this->viewState();

        // Bound the query to the requested page before indexData() runs its
        // element fetch, so indexElements() sees the offset/limit it needs —
        // e.g. assets page folders and files together through that method.
        $this->resolvePaginationState();

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
            context: static::RENDER_CONTEXT,
            selectable: true,
            sortable: false,
        );
    }

    /**
     * Resolves the current page's bounds and total, and applies the
     * offset/limit to the shared query so the element type's own
     * {@see indexElements()} fetch (via {@see indexData()}) returns exactly
     * this page. The total comes from {@see indexElementCount()}, which for
     * assets includes the folders merged into each page. Out-of-range pages
     * clamp to the last valid page.
     *
     * @return array{perPage: int, page: int, total: int, pageParam: string}
     */
    private function resolvePaginationState(): array
    {
        if ($this->paginationState !== null) {
            return $this->paginationState;
        }

        [$sourceKey] = $this->sourceState();
        $query = $this->resolveQuery();

        $perPage = max(1, $this->request->integer('per_page', 50));
        $pageParam = Cms::config()->getPageTriggerParam();

        $total = $this->elementType::indexElementCount($query, $sourceKey);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $this->request->integer($pageParam, 1)), $lastPage);

        $query->offset(($page - 1) * $perPage)->limit($perPage);

        return $this->paginationState = [
            'perPage' => $perPage,
            'page' => $page,
            'total' => $total,
            'pageParam' => $pageParam,
        ];
    }

    /**
     * Builds the paginator from the page elements fetched through the element
     * type's index pipeline (so assets keep their folders) and the resolved
     * page state.
     */
    /** @return LengthAwarePaginator<array-key, ElementInterface|array<string, mixed>> */
    private function resolvePaginator(): LengthAwarePaginator
    {
        if ($this->paginator !== null) {
            return $this->paginator;
        }

        // Ordering, attribute prep, and page bounds are applied here; the
        // resulting elements already reflect this page (folders + rows).
        $indexData = $this->resolveIndexData();
        $state = $this->resolvePaginationState();

        $paginator = new IlluminatePaginator(
            $indexData['elements'] ?? [],
            $state['total'],
            $state['perPage'],
            $state['page'],
            [
                'path' => IlluminatePaginator::resolveCurrentPath(),
                'pageName' => $state['pageParam'],
            ],
        );

        return $this->paginator = $paginator->appends(
            $this->request->except($state['pageParam']),
        );
    }

    /**
     * Serializes elements as table rows: the title renders as a CpLink-wrapped
     * chip; every other visible column renders through the element's
     * attribute-HTML pipeline (which element types override for attributes
     * like `authors`). Only the visible columns render — the client refetches
     * when its column selection changes.
     *
     * @param  list<ElementInterface>  $elements
     * @return list<array<string, mixed>>
     */
    private function tableRows(array $elements): array
    {
        $attributes = array_values(array_unique(['title', ...$this->resolveVisibleColumns()]));
        $elementHtml = app(ElementHtml::class);

        return array_map(fn (ElementInterface $element) => [
            'id' => $this->rowId($element),
            ...$this->extraRowData($element),
            ...collect($attributes)
                ->mapWithKeys(fn (string $attribute) => [
                    $attribute => $attribute === 'title'
                        ? $this->titleCellHtml($element, $elementHtml)
                        : (string) $element->getAttributeHtml($attribute),
                ])
                ->all(),
        ], $elements);
    }

    /**
     * The title cell: an element's chip, wrapped in a CpLink to its edit screen.
     * Elements with no edit URL (e.g. asset folders, which navigate via their
     * own row handler) render the bare chip, so no stray anchor intercepts the
     * row's click.
     */
    private function titleCellHtml(ElementInterface $element, ElementHtml $elementHtml): string
    {
        $chip = $elementHtml->elementChipHtml($element, [
            'context' => static::RENDER_CONTEXT,
            'appearance' => 'plain',
        ]);

        $editUrl = $element->getCpEditUrl();

        if ($editUrl === null) {
            return $chip;
        }

        // `:inertia`, bound — a plain `inertia => false` renders nothing at all
        // (Html::tag drops false attributes), so the prop falls back to its
        // `true` default and the title becomes an Inertia <Link> that navigates
        // on click. The element edit screen isn't an Inertia page, so that
        // visit only ends in a hard redirect anyway.
        return Html::tag('CpLink', $chip, ['href' => $editUrl, ':inertia' => 'false']);
    }

    /**
     * Serializes elements as server-rendered card parts for the cards view.
     * Vue owns the selection process, so cards render non-selectable.
     *
     * @param  list<ElementInterface>  $elements
     * @return list<array<string, mixed>>
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
                'context' => static::RENDER_CONTEXT,
                // Folders (no edit URL) navigate via their own card handler, so
                // don't wrap them in a link that would swallow the click.
                'hyperlink' => $element->getCpEditUrl() !== null,
                'showEditButton' => false,
                'autoReload' => false,
                'selectable' => false,
                'sortable' => false,
            ];

            return [
                'id' => $this->rowId($element),
                ...$this->extraRowData($element),
                'cardAttributes' => $elementHtml->elementCardAttributes($element, $cardConfig),
                'cardHeaderHtml' => $elementHtml->elementCardHeaderHtml($element, $cardConfig),
                'cardContentHtml' => $elementHtml->elementCardContentHtml($element, $cardConfig),
                'cardFooterHtml' => $elementHtml->elementCardFooterHtml($element, $cardConfig),
            ];
        }, $elements);
    }

    /**
     * Serializes elements for the thumbnail grid: a large thumbnail image, the
     * element label, and its edit URL. The client lays these out as tiles (see
     * the `ElementThumbs` component); folders navigate via their own row data.
     *
     * @param  ElementInterface[]  $elements
     * @return list<array<string, mixed>>
     */
    private function thumbData(array $elements): array
    {
        return array_map(fn (ElementInterface $element) => [
            'id' => $this->rowId($element),
            ...$this->extraRowData($element),
            'label' => $element->getUiLabel(),
            'url' => $element->getCpEditUrl(),
            'thumbHtml' => $element->getThumbHtml(self::THUMB_SIZE),
        ], $elements);
    }

    /**
     * A stable, unique row id for the client's table/selection keying. Defaults
     * to the element id; element types with non-element rows (e.g. asset
     * folders, which have no element id) override this to stay collision-free.
     */
    protected function rowId(ElementInterface $element): string|int|null
    {
        return $element->id;
    }

    /**
     * Extra per-row payload merged into every serialized row across all view
     * modes. Empty by default; element-type view models add their own row
     * metadata (e.g. asset folder navigation) here.
     *
     * @return array<string, mixed>
     */
    protected function extraRowData(ElementInterface $element): array
    {
        return [];
    }
}
