<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Support\Arr;
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
    ) {}

    public function __invoke(Request $request, string $page, ?string $sectionHandle = null)
    {
        $elementType = Entry::class;
        $context = [
            'page' => $page,
            'sectionHandle' => $sectionHandle ?? '',
            'elementType' => $elementType,
        ];

        $sort = ! empty($request->array('sort')) ? $request->array('sort') : [
            ['field' => 'dateCreated', 'direction' => 'desc'],
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
        $elementQuery = $this->buildElementQueryState($elementType, $source, null)['query'];

        if ($request->has('status')) {
            $elementQuery->status($request->input('status'));
        }

        // Apply the requested sort as the authoritative order. The client treats
        // the URL as the source of truth for sorting, so reset any ordering that
        // was applied while building the query (Laravel's orderBy() appends
        // rather than replaces, which would otherwise demote the requested sort
        // to a tiebreaker) and then apply the requested columns in order.
        $orderBy = array_values(array_filter(
            $sort,
            fn ($sortItem) => ! empty($sortItem['field']),
        ));

        if (! empty($orderBy)) {
            $elementQuery->getQuery()->reorder();

            foreach ($orderBy as $sortItem) {
                if (($sortItem['direction'] ?? 'asc') === 'desc') {
                    $elementQuery->orderByDesc($sortItem['field']);
                } else {
                    $elementQuery->orderBy($sortItem['field']);
                }
            }
        }

        // get the return URL with `?` replaced with a token
        // (see https://github.com/craftcms/cms/issues/18923)
        if ($returnUrl = $request->input('returnUrl')) {
            $returnUrl = str_replace('?', ':QS:', $returnUrl);
        }

        // Paginate a clone so the query the legacy index HTML uses below stays
        // unbounded.
        $pageParam = Cms::config()->getPageTriggerParam();
        $paginator = (clone $elementQuery)->paginate(
            perPage: $request->integer('per_page', 50),
            pageName: $pageParam,
            page: $request->integer($pageParam, 1),
        );

        $fieldLayouts = $this->resolveFieldLayouts();
        $sortOptions = $this->elementSources->getSourceSortOptions($elementType, $sourceKey)
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'value' => $option['attribute'] ?? $option['orderBy'],
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();

        $tableColumns = $this->elementSources->getSourceTableAttributes($elementType, $sourceKey)
            ->map(fn (array $attribute, string $key) => [
                ...$attribute,
                'value' => $key,
            ])
            ->values()
            ->all();

        $defaultTableColumns = $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            fieldLayouts: $fieldLayouts,
        )
            ->map(fn (array $attribute) => $attribute[0])
            ->filter(fn (string $attribute) => $attribute !== 'title')
            ->values()
            ->all();

        // @TODO: this should be from the view state
        // $attributes = ['id', 'title', 'status', 'uri', 'dateUpdated', 'dateCreated'];
        $attributes = ['title', ...array_keys($elementType::tableAttributes()), ...collect($tableColumns)->pluck('value')->all()];
        $elements = collect($paginator->items())
            ->map(fn (ElementInterface $element) => [
                // `id` is not a rendered column; the table keys row selection by
                // it (see `getRowId`) so selection tracks elements across sorting
                // and pagination.
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

        $viewState = [
            ...$this->resolveViewState(),
            'showHeaderColumn' => true,
            'fieldLayouts' => $this->resolveFieldLayouts(),
            'returnUrl' => $returnUrl,
        ];

        $contentHtml = $elementType::indexHtml(
            elementQuery: $elementQuery,
            disabledElementIds: $request->array('disabledElementIds'),
            viewState: $viewState,
            sourceKey: $sourceKey,
            context: $renderContext,
            includeContainer: false,
            selectable: true,
            sortable: false,
        );

        // PrepareElementSourcesVariables seeds $context['tableColumns'] with the
        // full set of available attributes, keyed by attribute. Drop it so the
        // recursive Arr::merge below doesn't fold our source-specific list (a
        // sequential array) into that associative structure — the page expects a
        // plain array of {label, value}.
        unset($context['tableColumns']);

        return Inertia::render('content/Index', Arr::merge($context, [
            'status' => $request->input('status', ''),
            'source' => $this->resolveSource($elementType, $request->input('source', '*'), $renderContext)[1],
            'search' => $request->input('search'),
            'viewState' => $viewState,
            'statusOptions' => $statusOptions,
            'sortOptions' => $sortOptions,
            'tableColumns' => $tableColumns,
            'defaultTableColumns' => $defaultTableColumns,
            'data' => $elements,
            'sort' => $sort,
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
            'contentHtml' => $contentHtml,
        ]));
    }
}
