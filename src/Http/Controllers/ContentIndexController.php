<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function CraftCms\Cms\t;

/**
 * @TODO Make this more generic, for now I'm just replacing entries
 */
class ContentIndexController
{
    use InteractsWithElementIndexes;

    public function __construct(
        private readonly PrepareElementIndexVariables $prepareElementIndexVariables,
        private readonly PrepareElementToolbarVariables $prepareElementToolbarVariables,
        private readonly PrepareElementSourcesVariables $prepareElementSourcesVariables,
        private readonly ElementHtml $elementHtml,
        private readonly ElementSources $elementSources,
        private readonly ElementAttributeRenderer $attributeRenderer,
        private readonly ElementActions $elementActions,
    ) {}

    /**
     * Resolves the default sort for a source from its configured `defaultSort`
     * (`[attribute, direction]`), falling back to `dateCreated desc`.
     *
     * @return array<int, array{field: string, direction: string}>
     */
    private function defaultSortForSource(?array $source): array
    {
        $defaultSort = $source['defaultSort'] ?? null;

        if (is_array($defaultSort) && isset($defaultSort[0])) {
            return [[
                'field' => $defaultSort[0],
                'direction' => ($defaultSort[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ]];
        }

        return [['field' => 'dateCreated', 'direction' => 'desc']];
    }

    public function __invoke(Request $request, string $page, ?string $sectionHandle = null)
    {
        $elementType = Entry::class;
        $context = [
            'page' => $page,
            'sectionHandle' => $sectionHandle ?? '',
            'elementType' => $elementType,
        ];

        ($this->prepareElementIndexVariables)($context);
        ($this->prepareElementToolbarVariables)($context);
        ($this->prepareElementSourcesVariables)($context);

        $statusOptions = collect($context['elementStatuses'])
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->prepend(['label' => t('All'), 'value' => ''])
            ->values()
            ->all();

        $renderContext = 'index';
        [$sourceKey, $source] = $this->resolveSource($elementType, $request->input('source', '*'), $renderContext);

        // The view mode is sent as a string when the user
        // switches views (see the `useElementIndexViewMode` composable); fall
        // back to the persisted view state, then the default table mode.
        $mode = $request->input('viewMode') ?: ($this->resolveViewState()['mode'] ?? 'table');

        // A requested sort wins; otherwise fall back to the source's configured
        // `defaultSort` (e.g. `['postDate', 'desc']`), then a sensible default.
        $sort = ! empty($request->array('sort'))
            ? $request->array('sort')
            : $this->defaultSortForSource($source);

        $elementQuery = $this->buildElementQueryState($elementType, $source, null)['query'];

        $elementQuery->status($request->input('status') ?: null);

        if ($request->filled('search')) {
            $elementQuery->search($request->input('search'));
        }

        $returnUrl = $this->resolveReturnUrl();

        // The client treats the URL as the source of truth for sorting, so map
        // the requested sort into the view state's order/sort. indexData() then
        // applies the index's ordering — including structures
        $orderBy = array_values(array_filter(
            $sort,
            fn ($sortItem) => ! empty($sortItem['field']),
        ));

        $viewState = [
            ...$this->resolveViewState(),
            'mode' => $mode,
            'order' => $orderBy[0]['field'] ?? null,
            'sort' => $orderBy[0]['direction'] ?? 'asc',
            'orderHistory' => array_map(
                fn (array $sortItem) => [$sortItem['field'], $sortItem['direction'] ?? 'asc'],
                array_slice($orderBy, 1),
            ),
            'showHeaderColumn' => true,
            'fieldLayouts' => $this->resolveFieldLayouts(),
            'returnUrl' => $returnUrl,
        ];

        // Reset any ordering applied while building the query so the requested
        // sort stays authoritative, then let indexData() apply it.
        if ($orderBy) {
            $elementQuery->getQuery()->reorder();
        }

        // indexData() applies the ordering and table-attribute preparation to
        // the query and returns the shared index variables (structure info,
        // resolved columns, view flags) — the same data that backs the legacy
        // HTML index — so the two indexes stay in sync.
        $indexData = $elementType::indexData(
            elementQuery: $elementQuery,
            disabledElementIds: $request->array('disabledElementIds'),
            viewState: $viewState,
            sourceKey: $sourceKey,
            context: $renderContext,
            selectable: true,
            sortable: false,
        );

        // Paginate a clone so the query the legacy index HTML uses below stays
        // unbounded.
        $pageParam = Cms::config()->getPageTriggerParam();
        $paginator = (clone $elementQuery)->paginate(
            perPage: $request->integer('per_page', 50),
            pageName: $pageParam,
            page: $request->integer($pageParam, 1),
        );

        // Serialize the available bulk actions for the active source so the
        // Inertia bulk-actions bar can offer them. This index always runs in the
        // administrative `index` context (set above), so we mirror only the
        // resolved-source condition ElementIndexResource also guards on, to
        // avoid surfacing actions for an unmatched source.
        $actions = null;

        if (isset($sourceKey)) {
            $availableActions = $this->elementActions->availableActions($elementType, $sourceKey, $elementQuery);
            $actions = empty($availableActions)
                ? null
                : $this->elementActions->serializeActionItems($availableActions);
        }

        $fieldLayouts = $this->resolveFieldLayouts();
        $sortOptions = $this->elementSources->getSourceSortOptions($elementType, $sourceKey)
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'value' => $option['attribute'] ?? $option['orderBy'],
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();

        // Selectable columns: common attributes plus the source's field columns.
        $tableColumns = $this->elementSources->getAvailableTableAttributes($elementType)
            ->merge($this->elementSources->getSourceTableAttributes($elementType, $sourceKey))
            ->map(fn (array $attribute, string $key) => [
                'label' => $attribute['label'],
                'value' => $key,
            ])
            ->values();

        $defaultTableColumns = $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            fieldLayouts: $fieldLayouts,
        )
            ->map(fn (array $attribute) => $attribute[0])
            ->filter(fn (string $attribute) => $attribute !== 'title')
            ->values()
            ->all();

        if ($mode === 'cards') {
            /**
             * Cards send down a single server-rendered `cardHtml` (from
             * `Cp::elementCardHtml`) which will be rendered in the Vue
             * view. Vue owns the selection process.
             */
            $elements = collect($paginator->items())
                ->map(function (ElementInterface $element) use ($renderContext) {
                    // A per-element `id` is shared across the full card and its
                    // parts so the header/body/footer line up if they're
                    // recomposed client-side, while staying unique per card.
                    $cardConfig = [
                        'id' => sprintf('card-%s', mt_rand()),
                        'context' => $renderContext,
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
        } else {
            $attributes = ['title', ...array_keys($elementType::tableAttributes()), ...collect($tableColumns)->pluck('value')->all()];
            $elements = collect($paginator->items())
                ->map(fn (ElementInterface $element) => [
                    'id' => $element->id,
                    ...collect($attributes)
                        ->mapWithKeys(fn (string $attribute) => [
                            $attribute => $attribute === 'title' ?
                                Html::tag('CpLink',
                                    $this->elementHtml->chipHtml($element, [
                                        'context' => $renderContext,
                                        'appearance' => 'plain',
                                    ]),
                                    ['href' => $element->getCpEditUrl(), 'inertia' => false]
                                )
                                : (string) $this->attributeRenderer->render($element, $attribute),
                        ])
                        ->all(),
                ]);
        }

        return Inertia::render('content/Index', Arr::merge($context, [
            'status' => $request->input('status', ''),
            'source' => $this->resolveSource($elementType, $request->input('source', '*'), $renderContext)[1],
            'search' => $request->input('search'),
            'structure' => isset($indexData['structure'])
                ? ['id' => $indexData['structure']->id, 'editable' => $indexData['structureEditable'] ?? false]
                : null,
            'viewState' => $viewState,
            'statusOptions' => $statusOptions,
            'sortOptions' => $sortOptions,
            'tableColumns' => $tableColumns,
            'defaultTableColumns' => $defaultTableColumns,
            'data' => $elements,
            'actions' => $actions,
            'sort' => $sort,
            'publishableSections' => Sections::getPublishableSections()->values(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]));
    }
}
