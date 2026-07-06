<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use Inertia\Inertia;

use function CraftCms\Cms\t;

/**
 * @TODO Make this more generic, for now I'm just replacing entries
 */
class ContentIndexController
{
    private const string RENDER_CONTEXT = 'index';

    /**
     * Maps a section-handle route segment to its source key (`singles` for
     * Single sections, `section:{uid}` otherwise).
     */
    private function sourceKeyForSectionHandle(?string $sectionHandle): ?string
    {
        if ($sectionHandle === null || $sectionHandle === '') {
            return null;
        }

        if ($sectionHandle === 'singles') {
            return 'singles';
        }

        $section = Sections::getSectionByHandle($sectionHandle);

        return $section ? "section:$section->uid" : null;
    }

    public function __construct(
        private readonly PrepareElementIndexVariables $prepareElementIndexVariables,
        private readonly PrepareElementToolbarVariables $prepareElementToolbarVariables,
        private readonly PrepareElementSourcesVariables $prepareElementSourcesVariables,
        private readonly ElementIndexes $elementIndexes,
    ) {}

    public function __invoke(ElementIndexRequest $request, string $page, ?string $sectionHandle = null)
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

        // An explicit ?source= wins; otherwise a section-handle URL (e.g.
        // content/entries/blog, content/entries/singles) selects that source.
        $requestedSource = $request->input('source')
            ?? $this->sourceKeyForSectionHandle($sectionHandle)
            ?? '*';

        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $requestedSource, self::RENDER_CONTEXT);

        // The view mode is sent as a string when the user switches views (see
        // the `useElementIndexViewMode` composable); fall back to the persisted
        // view state, then the default table mode.
        $mode = $request->input('viewMode') ?: $request->viewState()['mode'];

        $sort = $this->elementIndexes->resolveSort($request->array('sort'), $source);

        $currentCondition = $request->condition();

        $elementQuery = $this->elementIndexes->buildQuery(
            elementType: $elementType,
            source: $source,
            condition: $currentCondition,
            status: $request->input('status'),
            search: $request->input('search'),
        );

        $fieldLayouts = $request->fieldLayouts();

        $visibleColumns = $this->elementIndexes->visibleTableColumns(
            elementType: $elementType,
            sourceKey: $sourceKey,
            requested: $request->array('columns'),
            fieldLayouts: $fieldLayouts,
        );

        $viewState = $this->elementIndexes->viewState(
            sort: $sort,
            mode: $mode,
            tableColumns: $visibleColumns,
            fieldLayouts: $fieldLayouts,
            returnUrl: $request->returnUrl(),
            clientViewState: $request->viewState(),
        );

        // Reset any ordering applied while building the query so the requested
        // sort stays authoritative, then let indexData() apply it.
        if ($viewState['order'] !== null) {
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
            context: self::RENDER_CONTEXT,
            selectable: true,
            sortable: false,
        );

        [$paginator, $pagination] = $this->elementIndexes->paginate(
            query: $elementQuery,
            perPage: $request->integer('per_page', 50),
            page: $request->integer(Cms::config()->getPageTriggerParam(), 1),
        );

        $tableColumns = $this->elementIndexes->availableTableColumns($elementType, $sourceKey);

        $elements = $mode === 'cards'
            ? $this->elementIndexes->cardData($paginator->items(), self::RENDER_CONTEXT)
            : $this->elementIndexes->tableRows($paginator->items(), $elementType, $visibleColumns, self::RENDER_CONTEXT);

        return Inertia::render('content/Index', Arr::merge($context, [
            'status' => $request->input('status', ''),
            'source' => $source,
            'search' => $request->input('search'),
            'structure' => isset($indexData['structure'])
                ? ['id' => $indexData['structure']->id, 'editable' => $indexData['structureEditable'] ?? false]
                : null,
            'currentCondition' => $currentCondition?->getConfig(),
            'viewState' => $viewState,
            'statusOptions' => $statusOptions,
            'sortOptions' => $this->elementIndexes->sortOptions($elementType, $sourceKey),
            'tableColumns' => $tableColumns,
            'defaultTableColumns' => $this->elementIndexes->defaultTableColumns(
                $elementType,
                $sourceKey,
                $fieldLayouts,
            ),
            'data' => $elements,
            'actions' => $this->elementIndexes->actionItems($elementType, $sourceKey, $elementQuery),
            'sort' => $sort,
            'publishableSections' => Sections::getPublishableSections()->values(),
            'pagination' => $pagination,
        ]));
    }
}
