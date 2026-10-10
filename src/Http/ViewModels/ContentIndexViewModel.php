<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as IlluminatePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\currentUser;
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
 *
 * @since 6.0.0
 */
abstract class ContentIndexViewModel extends ViewModel
{
    protected const string RENDER_CONTEXT = 'index';

    /** The thumbnail edge length (px) requested for the thumbnail grid view. */
    private const int THUMB_SIZE = 200;

    private ?ElementIndexSourceSettings $resolvedSourceSettings = null;

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
    protected ?array $resolvedSources = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $unfilteredSources = null;

    private ?Site $resolvedSite = null;

    /** @var Collection<int, Site>|null */
    private ?Collection $selectableSites = null;

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
        return $this->sourceSettings()->source;
    }

    /** @return array{id: int, editable: bool, maxLevels: int|null}|null */
    public function structure(): ?array
    {
        if ($this->sourceState()[0] === null) {
            return null;
        }

        $indexData = $this->resolveIndexData();

        return isset($indexData['structure'])
            ? [
                'id' => $indexData['structure']->id,
                'editable' => $indexData['structureEditable'] ?? false,
                // Bounds the depth a row can be dragged to; null means unlimited.
                'maxLevels' => $indexData['structure']->maxLevels ?: null,
            ]
            : null;
    }

    /**
     * Whether the index is listing drafts or trashed elements. Both disable
     * structure reordering: a move only makes sense against the canonical tree.
     */
    public function drafts(): bool
    {
        return filter_var(
            $this->request->criteria()['drafts'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public function trashed(): bool
    {
        return filter_var(
            $this->request->criteria()['trashed'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
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

        // Structure mode *is* the structure ordering: indexData() only walks the
        // tree (`structureId()` + `orderBy('lft')`) when it sees `order:
        // structure`, so the mode pins the order rather than leaving it to
        // whichever sort the user last chose for this source.
        $structureMode = $this->mode() === ElementIndexViewMode::Structure->value;

        // The client treats the URL as the source of truth for sorting, so the
        // requested sort maps into order/sort/orderHistory, which indexData()
        // then applies. The resolved visible columns go into `tableColumns` so
        // indexData() prepares (eager-loads) exactly what will render.
        return $this->resolvedViewState = [
            ...$this->request->viewState(),
            ...$this->sourceViewState(),
            'mode' => $this->mode(),
            'tableColumns' => $this->resolveVisibleColumns(),
            'order' => $structureMode ? 'structure' : ($orderBy[0]['field'] ?? null),
            'sort' => $structureMode ? 'asc' : ($orderBy[0]['direction'] ?? 'asc'),
            'orderHistory' => $structureMode ? [] : array_map(
                fn (array $sortItem) => [$sortItem['field'], $sortItem['direction'] ?? 'asc'],
                array_slice($orderBy, 1),
            ),
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
     * The site the index is listing, which everything else here is scoped to:
     * the `?site=` handle the rest of the CP addresses sites by.
     *
     * Taken at its word, even when this index has no sources for it. The site
     * crumb is shared chrome that offers every editable site, so silently
     * showing a different site's content than the one it names would be a lie;
     * an index with nothing on this site comes up empty instead.
     *
     * Not a payload key — the client gets {@see siteId()} and {@see Sites()}.
     */
    protected function site(): Site
    {
        return $this->resolvedSite ??= $this->requestedSite() ?? Sites::getCurrentSite();
    }

    /**
     * The site the request asked for.
     *
     * `RequestedSite` reads the `?site=` query param, which covers an index
     * page. The selector modal posts its index params in the body instead, so
     * an explicit `site` input is honored first and reaches both.
     */
    private function requestedSite(): ?Site
    {
        $handle = $this->request->input('site');

        if (is_string($handle) && $handle !== '') {
            $site = Sites::getSiteByHandle($handle, true);

            if ($site !== null) {
                return $site;
            }
        }

        return app(RequestedSite::class)->get();
    }

    public function siteId(): int
    {
        return $this->sourceSettings()->siteId;
    }

    /**
     * The sites the site menu offers, as the client needs them.
     *
     * Empty unless the menu is being shown at all, so a single-site install
     * (or an element type that isn't localized) ships nothing.
     *
     * @return list<array{id: int, handle: string, name: string, group: ?string}>
     */
    public function sites(): array
    {
        if (! $this->showSiteMenu()) {
            return [];
        }

        return $this->selectableSites()
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'handle' => $site->handle,
                'name' => t($site->name, category: 'site'),
                // Only a heading for the menu, so an unresolvable group leaves
                // the site ungrouped rather than failing the whole payload.
                'group' => ($group = SiteGroups::getGroupById($site->groupId)) !== null
                    ? t($group->name, category: 'site')
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * The editable sites this index has something to show on.
     *
     * When every source is site-specific the list narrows to the sites those
     * sources between them cover, so the menu never offers a site the index
     * would come up empty on. Mirrors {@see ElementIndexHtml}.
     *
     * @return Collection<int, Site>
     */
    protected function selectableSites(): Collection
    {
        if ($this->selectableSites !== null) {
            return $this->selectableSites;
        }

        $sites = Sites::getEditableSites();
        $sources = collect($this->allSources())
            ->reject(fn (array $source): bool => $source['type'] === ElementSources::TYPE_HEADING);

        if ($sources->isNotEmpty() && $sources->every(fn (array $source): bool => isset($source['sites']))) {
            $represented = $sources->flatMap(fn (array $source): array => (array) $source['sites'])->all();
            $narrowed = $sites->filter(fn (Site $site): bool => in_array($site->id, $represented));

            // Every source being scoped away from all the editable sites would
            // leave no menu at all; keep the full list rather than none.
            if ($narrowed->isNotEmpty()) {
                $sites = $narrowed;
            }
        }

        return $this->selectableSites = $sites->values();
    }

    /**
     * The element index page title: the selected source's name — “All entries”,
     * “Posts”, a volume, a user group — since that's what the screen is
     * actually showing.
     *
     * The screen it belongs to is named by the breadcrumb above it
     * ({@see crumbs()}), so the title doesn't repeat it. Falls back to the
     * index's own name — a custom index page's, otherwise the element type's
     * plural display name — when no source resolves.
     */
    public function title(): string
    {
        $sourceLabel = $this->sourceState()[1]['label'] ?? null;

        if (is_string($sourceLabel) && $sourceLabel !== '') {
            return $sourceLabel;
        }

        return $this->indexTitle();
    }

    /**
     * The index screen's own name: a custom index page's wins, otherwise the
     * element type's plural display name.
     */
    protected function indexTitle(): string
    {
        if ($this->page !== null) {
            $pageName = $this->sources()[0]['page'] ?? null;

            if ($pageName !== null) {
                return t($pageName, category: 'site');
            }
        }

        return $this->elementType::pluralDisplayName();
    }

    /**
     * The breadcrumb trail for the CP header bar (`PageScreen`'s `crumbs` page
     * prop): the index screen itself, then the selected source — including the
     * “all elements” source the bare index opens on, which gets a crumb of its
     * own (“All entries”, “All users”) rather than being left implicit.
     *
     * The source crumb carries the screen's other sources as an action menu, so
     * it doubles as a source switcher. That's the same trail, in the same
     * shape, that an element's own edit screen opens with (see the element
     * types' own `crumbs()`) — so stepping from an index into an element on it
     * doesn't move the breadcrumbs around.
     *
     * Screens with no URL of their own ({@see indexUrl()}) opt out: the element
     * selector modal has no header to put a trail in.
     *
     * @return list<ActionItem|array<string, mixed>>
     */
    public function crumbs(): array
    {
        $indexUrl = $this->indexUrl();

        if ($indexUrl === null) {
            return [];
        }

        $crumbs = [
            // The index's own name, not title() — that now names the selected
            // source, which is the crumb below this one. The site crumb that
            // leads the whole trail is shared chrome ({@see SiteSwitcher}),
            // added by the screen rather than by any one index.
            new ActionItem()->label($this->indexTitle())->href($indexUrl),
        ];

        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return $crumbs;
        }

        $options = $this->sourceCrumbOptions($sourceKey);
        // The options mirror the sources sidebar, headings and all, so the
        // selectable ones are a level down inside any group.
        $choices = collect($options)->flatMap(
            fn (array $option): array => $option['type'] === 'group' ? $option['items'] : [$option],
        );
        $current = $choices->first(fn (array $option): bool => $option['selected']);

        if ($current === null) {
            return $crumbs;
        }

        // A crumb is an action item like the options are, so the full set
        // doubles as the current one's switcher menu. One source is no choice
        // at all, so it gets a plain crumb. It names the page you're on, so it
        // doesn't link to it.
        $crumbs[] = new ActionItem()
            ->label($current['label'])
            ->items($choices->count() > 1 ? $options : []);

        return $crumbs;
    }

    /**
     * The index's sources, as the active site sees them: anything scoped to
     * other sites is gone, and headings left over nothing with it.
     *
     * Everything downstream reads this rather than {@see allSources()}, so a
     * source hidden for the site can't be the crumb switcher's current option
     * and can't be resolved by {@see sourceState()} either — which is how the
     * selected source clears itself when the site changes out from under it.
     *
     * @return list<array<string, mixed>>
     */
    public function sources(): array
    {
        return $this->resolvedSources ??= ElementSources::filterSourcesBySite(
            collect($this->allSources()),
            $this->siteId(),
        )->all();
    }

    /**
     * Every source on the index, before the active site narrows them down.
     *
     * Resolving the site needs these ({@see selectableSites()}), so this is
     * the one place that can't ask which site is active.
     *
     * @return list<array<string, mixed>>
     */
    protected function allSources(): array
    {
        // The context is deliberately left at ElementSources' `index` default,
        // which is what this method has always resolved under. (sourceState()
        // passes static::RENDER_CONTEXT explicitly — the same value today.)
        return $this->unfilteredSources ??= $this->indexState()->sources(
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
        return $this->sourceSettings()->viewModes;
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
        return $this->sourceSettings()->publicSortOptions();
    }

    /**
     * Selectable columns: common attributes plus the source's field columns.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function tableColumns(): array
    {
        return $this->sourceSettings()->tableColumns;
    }

    /** @return string[] */
    public function defaultTableColumns(): array
    {
        return $this->sourceSettings()->defaultTableColumns;
    }

    /** @return list<array<string, mixed>> */
    public function data(): array
    {
        if ($this->sourceState()[0] === null) {
            return [];
        }

        $elements = $this->resolvePaginator()->items();
        $this->prepareElements($elements);

        return match ($this->mode()) {
            ElementIndexViewMode::Cards->value => $this->cardData($elements),
            ElementIndexViewMode::Thumbs->value => $this->thumbData($elements),
            default => $this->tableRows($elements),
        };
    }

    /** @param list<ElementInterface|array<string, mixed>> $elements */
    protected function prepareElements(array $elements): void {}

    /** @return array<int, array<string, mixed>>|null */
    public function actions(): ?array
    {
        [$sourceKey] = $this->sourceState();

        if ($sourceKey === null) {
            return null;
        }

        $sourceSettings = $this->sourceSettings();

        if (! $sourceSettings->actionsEnabled) {
            return [];
        }

        $availableActions = ElementActions::availableActions($this->elementType, $sourceKey, $this->resolveQuery());

        return empty($availableActions)
            ? null
            : ElementActions::serializeActionItems($availableActions);
    }

    /** @return array<int, array<string, mixed>> */
    public function exporters(): array
    {
        [$sourceKey] = $this->sourceState();

        $exporters = $sourceKey === null
            ? null
            : app(ElementIndexes::class)->availableExporters($this->elementType, $sourceKey, $this->request->isMobileBrowser());

        return $exporters ? ElementExporters::serializeExporters($exporters) : [];
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

        return $this->resolvedSort = $this->sourceSettings()->sort($this->request->array('sort'));
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
                'per_page' => $this->sourceSettings()->pageSize,
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

    /**
     * The index screen's own URL — the breadcrumb trail's first crumb, and what
     * per-source URLs hang off.
     *
     * `null` (the default) opts the screen out of breadcrumbs entirely; see
     * {@see crumbs()} for which screens do and why.
     */
    protected function indexUrl(): ?string
    {
        return null;
    }

    /**
     * The URL that selects a source on this index.
     *
     * Defaults to the `?source=` query the sidebar's source links use. Screens
     * whose sources have tidier URLs of their own — a section handle, a user
     * group slug — override this so a crumb lands on the same URL the rest of
     * the CP links that source by.
     *
     * @param  array<string, mixed>  $source
     */
    protected function sourceUrl(array $source): ?string
    {
        $indexUrl = $this->indexUrl();

        if ($indexUrl === null) {
            return null;
        }

        // The “all elements” source is what the bare index shows, so it's
        // addressed by the index's own URL rather than a query naming it.
        return $source['key'] === '*'
            ? $indexUrl
            : Url::urlWithParams($indexUrl, ['source' => (string) $source['key']]);
    }

    /**
     * The index's sources as navigation entries, headings and all.
     *
     * The control panel puts an index's sources in the secondary nav, which a
     * screen fills from its `subnav` prop. A heading becomes a group over the
     * sources that follow it, the same way the sidebar drew it.
     *
     * @return list<NavItem>
     */
    public function sourceNavItems(): array
    {
        if ($this->indexUrl() === null) {
            return [];
        }

        [$sourceKey] = $this->sourceState();
        $items = [];
        $group = null;

        foreach ($this->sources() as $source) {
            if ($source['type'] === ElementSources::TYPE_HEADING) {
                $items[] = new NavItem()
                    ->label($source['heading'] ?? '')
                    ->group(true)
                    ->subnav([]);
                $group = array_key_last($items);

                continue;
            }

            $key = $source['key'] ?? null;
            $url = $key !== null ? $this->sourceUrl($source) : null;

            if ($url === null) {
                continue;
            }

            $item = new NavItem()
                ->label($source['label'] ?? $key)
                ->href($url)
                ->selected($key === $sourceKey)
                ->status($source['status'] ?? null)
                ->badgeCount((int) ($source['badgeCount'] ?? 0));

            if ($group === null) {
                $items[] = $item;

                continue;
            }

            $items[$group]->add($item);
        }

        /** A heading that collected nothing has nothing to head. */
        return array_values(array_filter(
            $items,
            fn (NavItem $item): bool => ! $item->group || $item->subnav !== [],
        ));
    }

    /**
     * The index's sources as action items, with the current one flagged — the
     * source crumb, and the switcher menu hanging off it.
     *
     * Headings come through as groups over the sources they head, so the menu
     * reads the same as the sources sidebar beside it rather than flattening
     * into an ungrouped list.
     *
     * @return list<
     *     array{type: 'link', label: string, href: string, selected: bool}
     *     |array{type: 'group', heading: string, items: list<array{type: 'link', label: string, href: string, selected: bool}>}
     * >
     */
    private function sourceCrumbOptions(string $sourceKey): array
    {
        $options = [];
        // A heading collects the sources after it, so the switcher groups them
        // the way the sources sidebar does.
        $groupIndex = null;

        foreach ($this->sources() as $source) {
            if ($source['type'] === ElementSources::TYPE_HEADING) {
                $options[] = [
                    'type' => 'group',
                    'heading' => $source['heading'] ?? '',
                    'items' => [],
                ];
                $groupIndex = array_key_last($options);

                continue;
            }

            $key = $source['key'] ?? null;

            if ($key === null) {
                continue;
            }

            $url = $this->sourceUrl($source);

            if ($url === null) {
                continue;
            }

            $option = [
                'type' => 'link',
                'label' => $source['label'] ?? $key,
                'href' => $url,
                'selected' => $key === $sourceKey,
            ];

            if ($groupIndex !== null) {
                $options[$groupIndex]['items'][] = $option;
            } else {
                $options[] = $option;
            }
        }

        // A heading whose sources the user can't reach is left standing on its
        // own, so drop it rather than show a heading over nothing.
        return array_values(array_filter(
            $options,
            fn (array $option): bool => $option['type'] !== 'group' || count($option['items']) > 0,
        ));
    }

    /** @return array{0: ?string, 1: ?array<string, mixed>} */
    protected function sourceState(): array
    {
        $settings = $this->sourceSettings();

        return [$settings->sourceKey, $settings->source];
    }

    protected function resolveSourceSettings(): ElementIndexSourceSettings
    {
        $siteId = $this->site()->id;
        [$sourceKey, $source] = $this->resolvePageSourceState($siteId);
        $fieldLayouts = $this->request->fieldLayouts();
        $sortOptions = $sourceKey === null ? [] : $this->resolvePageSortOptions($sourceKey, $source, $fieldLayouts);
        $tableColumns = $sourceKey === null
            ? []
            : $this->indexState()
                ->tableColumns($this->elementType, $sourceKey)
                ->map(fn (array $attribute, string $key): array => [
                    'label' => $attribute['label'],
                    'value' => $key,
                ])
                ->values()
                ->all();
        $defaultTableColumns = $sourceKey === null
            ? []
            : ElementSources::getTableAttributes(
                elementType: $this->elementType,
                sourceKey: $sourceKey,
                fieldLayouts: $fieldLayouts,
            )
                ->map(fn (array $attribute) => $attribute[0])
                ->filter(fn (string $attribute) => $attribute !== 'title')
                ->values()
                ->all();
        $sourceDefaultSort = $source['defaultSort'] ?? null;
        $defaultSort = is_array($sourceDefaultSort) && is_string($sourceDefaultSort[0] ?? null)
            ? [[
                'field' => $sourceDefaultSort[0],
                'direction' => ($sourceDefaultSort[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ]]
            : [['field' => 'dateCreated', 'direction' => 'desc']];

        return new ElementIndexSourceSettings(
            sourceKey: $sourceKey,
            source: $source,
            siteId: $siteId,
            viewModes: $this->elementType::indexViewModes(),
            sortOptions: $sortOptions,
            tableColumns: $tableColumns,
            defaultTableColumns: $defaultTableColumns,
            defaultSort: $defaultSort,
            defaultViewMode: is_string($source['defaultViewMode'] ?? null) ? $source['defaultViewMode'] : 'table',
            pageSize: max(1, $this->request->integer('per_page', 50)),
            showHeaderColumn: $this->request->boolean('showHeaderColumn', true),
            fieldLayouts: $fieldLayouts,
        );
    }

    /** @return array<string, mixed> */
    protected function sourceViewState(): array
    {
        return $this->sourceSettings()->viewState();
    }

    /** @return array{0: ?string, 1: ?array<string, mixed>} */
    private function resolvePageSourceState(int $siteId): array
    {
        // An explicit ?source= wins; otherwise the element type's default
        // (e.g. a section-handle or volume-path URL) selects its source.
        $requestedSource = $this->request->input('source') ?? $this->defaultSourceKey();

        if ($requestedSource !== null) {
            $resolved = app(ElementIndexes::class)
                ->resolveSource($this->elementType, $requestedSource, static::RENDER_CONTEXT);

            // resolveSource() looks the key up directly, so it'll happily find
            // a source the active site doesn't have — the case Craft 5 handles
            // by clearing the selection in updateSourceVisibility(). Fall
            // through to a visible source rather than list a hidden one.
            if (
                $resolved[0] !== null &&
                ($resolved[1] === null || ElementSources::sourceIsAvailableForSite($resolved[1], $siteId))
            ) {
                return $resolved;
            }
        }

        // Not every element type has a `*` source (assets index per-volume,
        // for example), so mirror the legacy index's behavior and fall back
        // to the first available source.
        // Headings are keyed too, but only head the sources beneath them.
        $sources = array_filter($this->sourceCandidates($siteId), fn (array $source): bool => isset($source['key'])
            && ($source['type'] ?? null) !== ElementSources::TYPE_HEADING
            && ! ($source['disabled'] ?? false));
        $source = ($requestedSource === null ? array_find($sources, fn (array $source): bool => $source['key'] === '*') : null)
            ?? array_first($sources);

        return [$source['keyPath'] ?? $source['key'] ?? null, $source];
    }

    /** @return list<array<string, mixed>> */
    protected function sourceCandidates(int $siteId): array
    {
        return ElementSources::filterSourcesBySite(collect($this->allSources()), $siteId)->all();
    }

    /**
     * @param  array<string, mixed>|null  $source
     * @param  array<int, FieldLayout>|null  $fieldLayouts
     * @return list<array{label: string, value: string, defaultDir: string, fixedDir?: string}>
     */
    private function resolvePageSortOptions(string $sourceKey, ?array $source, ?array $fieldLayouts): array
    {
        $indexState = $this->indexState();
        $options = [];

        if (isset($source['structureId'])) {
            $options['structure'] = [
                'label' => t('Structure'),
                'value' => 'structure',
                'defaultDir' => 'asc',
            ];
        }

        foreach ($indexState->sortOptions($this->elementType) as $option) {
            $this->addSortOption($options, $option);
        }

        foreach (ElementSources::getSourceSortOptions($this->elementType, $sourceKey, $fieldLayouts) as $key => $option) {
            $this->addSortOption($options, $indexState->normalizeSortOption($option, $key));
        }

        return array_values($options);
    }

    /**
     * @param  array<string, array{label: string, value: string, defaultDir: string, fixedDir?: string}>  $options
     * @param  array{attribute: mixed, defaultDir: mixed, label: mixed, option: mixed, ...}  $option
     */
    private function addSortOption(array &$options, array $option): void
    {
        $value = self::addressableSortAttribute($option);

        if ($value === null || isset($options[$value])) {
            return;
        }

        $rawOption = is_array($option['option']) ? $option['option'] : [];
        $fixedDirection = $rawOption['fixedDir'] ?? null;
        $options[$value] = [
            'label' => is_string($option['label']) ? $option['label'] : $value,
            'value' => $value,
            'defaultDir' => $option['defaultDir'] === 'desc' ? 'desc' : 'asc',
            ...(in_array($fixedDirection, ['asc', 'desc'], true) ? ['fixedDir' => $fixedDirection] : []),
        ];
    }

    /**
     * The shared server-side index state — source, column, sort-option and
     * menu resolution, shared with the server-rendered index shell.
     */
    protected function indexState(): ElementIndexState
    {
        return app(ElementIndexState::class);
    }

    private function sourceSettings(): ElementIndexSourceSettings
    {
        return $this->resolvedSourceSettings ??= $this->resolveSourceSettings();
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
    protected function mode(): string
    {
        return $this->request->input('viewMode')
            ?: $this->request->input('viewState.mode')
            ?: $this->sourceSettings()->defaultViewMode;
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
            baseCriteria: $this->baseCriteria(),
            criteria: static::RENDER_CONTEXT === ElementSources::CONTEXT_MODAL ? $this->request->criteria() : [],
            // Collapsed subtrees are excluded by the query itself, so a
            // collapsed branch never reaches the client (and never counts
            // toward pagination). Only structure mode collapses anything —
            // honoring the param elsewhere would silently hide flat rows.
            collapsedElementIds: $this->mode() === ElementIndexViewMode::Structure->value
                ? $this->request->collapsedElementIds()
                : [],
        )['query'];

        // Ahead of the source's own criteria, which shouldn't get to override
        // the site the user picked (the legacy index drops `criteria.siteId`
        // for the same reason).
        $query->siteId($this->siteId());

        $query->status($this->status() ?: ($this->sourceState()[1]['criteria']['status'] ?? null));

        if (($search = $this->search()) !== null && $search !== '') {
            $query->search($search);
        }

        return $this->query = $query;
    }

    /**
     * Includes saved unpublished drafts alongside canonical elements, matching
     * the baseline criteria used by the Craft 5 element index.
     *
     * @return array<string, mixed>
     */
    protected function baseCriteria(): array
    {
        return [
            'drafts' => $this->canHaveDrafts() ? null : false,
            'draftOf' => false,
            'savedDraftsOnly' => true,
            ...($this->sourceState()[1]['criteria'] ?? []),
        ];
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
        $viewState = [
            ...$this->viewState(),
            ...$this->indexViewState(),
        ];

        // Bound the query to the requested page before indexData() runs its
        // element fetch, so indexElements() sees the offset/limit it needs —
        // e.g. assets page folders and files together through that method.
        $this->resolvePaginationState();

        // Reset any ordering applied while building the query so the requested
        // sort stays authoritative, then let indexData() apply it.
        if ($viewState['order'] !== null && $this->shouldResetQueryOrder($viewState['order'])) {
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

    /** @return array<string, mixed> */
    protected function indexViewState(): array
    {
        return $this->sourceSettings()->indexViewState();
    }

    protected function shouldResetQueryOrder(string $order): bool
    {
        return $this->sourceSettings()->shouldResetQueryOrder($order);
    }

    /** @return array{perPage: int, page: int, total: int, pageParam: string} */
    private function resolvePaginationState(): array
    {
        if ($this->paginationState !== null) {
            return $this->paginationState;
        }

        [$sourceKey] = $this->sourceState();
        $query = $this->resolveQuery();

        $perPage = $this->sourceSettings()->pageSize;
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

    /** @return LengthAwarePaginator<array-key, ElementInterface|array<string, mixed>> */
    private function resolvePaginator(): LengthAwarePaginator
    {
        if ($this->paginator !== null) {
            return $this->paginator;
        }

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
     * @param  list<ElementInterface>  $elements
     * @return list<ActionItem|array<string, mixed>>
     */
    private function tableRows(array $elements): array
    {
        $attributes = array_values(array_unique(['title', ...$this->resolveVisibleColumns()]));
        $elementHtml = app(ElementHtml::class);
        $descendantFlags = $this->mode() === ElementIndexViewMode::Structure->value
            ? $this->descendantFlags(array_values($elements))
            : [];

        return array_map(fn (ElementInterface $element) => [
            'id' => $this->rowId($element),
            ...$this->baseRowData($element, $elementHtml),
            ...$this->structureRowData($element, $descendantFlags[$element->id] ?? false),
            ...$this->inlineRowData($element, $attributes),
            ...collect($attributes)
                ->mapWithKeys(fn (string $attribute) => [
                    $attribute => $attribute === 'title'
                        ? $this->titleCellHtml($element, $elementHtml)
                        : (string) $element->getAttributeHtml($attribute),
                ])
                ->all(),
        ], $elements);
    }

    /** @return array<string, mixed> */
    private function structureRowData(ElementInterface $element, bool $hasDescendants): array
    {
        if ($this->mode() !== ElementIndexViewMode::Structure->value || ! isset($element->level)) {
            return [];
        }

        return [
            'level' => (int) $element->level,
            'hasDescendants' => $hasDescendants,
            'siteId' => $element->siteId,
        ];
    }

    /**
     * @param  list<ElementInterface>  $elements
     * @return array<int, bool>
     */
    private function descendantFlags(array $elements): array
    {
        $baseQuery = $this->resolveIndexData()['elementQuery'] ?? null;
        $collapsedIds = array_map(intval(...), $this->request->collapsedElementIds());
        $flags = [];

        foreach ($elements as $index => $element) {
            if (! isset($element->lft, $element->rgt, $element->level) || $element->rgt <= $element->lft + 1) {
                $flags[$element->id] = false;

                continue;
            }

            $next = $elements[$index + 1] ?? null;

            if ($next !== null && ! in_array($element->id, $collapsedIds, true)) {
                $flags[$element->id] = $next->level > $element->level;

                continue;
            }

            $flags[$element->id] = $baseQuery instanceof ElementQueryInterface && (clone $baseQuery)
                ->offset(null)
                ->limit(null)
                ->structureId($element->structureId)
                ->descendantOf($element)
                ->exists();
        }

        return $flags;
    }

    private function titleCellHtml(ElementInterface $element, ElementHtml $elementHtml): string
    {
        $chip = $elementHtml->elementChipHtml($element, [
            'context' => static::RENDER_CONTEXT,
            'appearance' => 'plain',
        ]);

        if (static::RENDER_CONTEXT === ElementSources::CONTEXT_MODAL) {
            return $chip;
        }

        return $this->titleLinkHtml($element, $chip);
    }

    /**
     * Links a title cell's chip to wherever clicking the element should go.
     */
    protected function titleLinkHtml(ElementInterface $element, string $chip): string
    {
        $editUrl = $this->editUrl($element);

        if ($editUrl === null) {
            return $chip;
        }

        return Html::tag('CpLink', $chip, ['href' => $editUrl]);
    }

    /**
     * @param  list<ElementInterface>  $elements
     * @return list<ActionItem|array<string, mixed>>
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
                'hyperlink' => $this->editUrl($element) !== null,
                'showEditButton' => false,
                'autoReload' => false,
                'selectable' => false,
                'sortable' => false,
                ...$this->cardConfig($element),
            ];

            return [
                'id' => $this->rowId($element),
                ...$this->baseRowData($element, $elementHtml),
                ...$elementHtml->elementCardData($element, $cardConfig),
            ];
        }, $elements);
    }

    /** @return array<string, mixed> */
    protected function cardConfig(ElementInterface $element): array
    {
        return [];
    }

    /**
     * @param  ElementInterface[]  $elements
     * @return list<ActionItem|array<string, mixed>>
     */
    private function thumbData(array $elements): array
    {
        return array_map(fn (ElementInterface $element) => [
            'id' => $this->rowId($element),
            ...$this->baseRowData($element),
            'url' => static::RENDER_CONTEXT !== ElementSources::CONTEXT_MODAL
                ? $this->editUrl($element)
                : null,
            'thumbHtml' => $element->getThumbHtml(self::THUMB_SIZE, ImageTransformMode::Fit),
        ], $elements);
    }

    /**
     * @return array{cpEditUrl: ?string, viewUrl: ?string}
     */
    private function elementUrls(ElementInterface $element): array
    {
        return [
            'cpEditUrl' => currentUser()?->can('view', $element) ? $element->getCpEditUrl() : null,
            'viewUrl' => $element->getUrl(),
        ];
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

    /** @return array<string, mixed> */
    protected function extraRowData(ElementInterface $element): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    private function baseRowData(ElementInterface $element, ?ElementHtml $elementHtml = null): array
    {
        $elementHtml ??= app(ElementHtml::class);

        return [
            'label' => $element->getUiLabel(),
            ...$this->elementUrls($element),
            'capabilities' => $elementHtml->elementCapabilities($element, static::RENDER_CONTEXT),
            ...$this->extraRowData($element),
        ];
    }

    /**
     * @param  list<string>  $attributes
     * @return array<string, mixed>
     */
    private function inlineRowData(ElementInterface $element, array $attributes): array
    {
        if (
            static::RENDER_CONTEXT === ElementSources::CONTEXT_MODAL ||
            ($this->viewState()['static'] ?? false) ||
            Gate::denies('save', $element)
        ) {
            return [];
        }

        return [
            'inlineEditable' => true,
            ...($this->request->boolean('editable') ? [
                'inlineInputHtml' => collect($attributes)
                    ->mapWithKeys(fn (string $attribute): array => [
                        $attribute => InputNamespace::with(
                            'inline',
                            fn (): string => InputNamespace::namespaceInputs(
                                fn (): string => (string) $element->getInlineAttributeInputHtml($attribute),
                                "element-$element->id".(str_starts_with($attribute, 'field:') || str_starts_with($attribute, 'fieldInstance:') || str_starts_with($attribute, 'contentBlock:') ? '[fields]' : ''),
                            ),
                        ),
                    ])
                    ->all(),
            ] : []),
        ];
    }

    protected function editUrl(ElementInterface $element): ?string
    {
        return $element->getCpEditUrl();
    }
}
