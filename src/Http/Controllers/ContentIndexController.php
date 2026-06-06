<?php

declare(strict_types = 1);

namespace CraftCms\Cms\Http\Controllers;

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
    ) {
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
            ->map(fn($label, $value) => ['label' => $label, 'value' => $value])
            ->prepend(['label' => t('All'), 'value' => ''])
            ->values()
            ->all();

        $renderContext = 'index';
        [$sourceKey, $source] = $this->resolveSource($elementType, $request->input('source', '*'), $renderContext);
        $elementQuery = $this->buildElementQueryState($elementType, $source, null)['query'];

        if ($request->has('status')) {
            $elementQuery->status($request->input('status'));
        }

        // get the return URL with `?` replaced with a token
        // (see https://github.com/craftcms/cms/issues/18923)
        if ($returnUrl = $request->input('returnUrl')) {
            $returnUrl = str_replace('?', ':QS:', $returnUrl);
        }

        // @TODO: this should be from the view state
        // $attributes = ['id', 'title', 'status', 'uri', 'dateUpdated', 'dateCreated'];
        $attributes = ['title', ...array_keys($elementType::tableAttributes())];
        $elements = collect($elementType::indexElements($elementQuery, $sourceKey))
            ->map(fn(ElementInterface $element) => collect($attributes)
                ->mapWithKeys(fn(string $attribute) => [
                    $attribute => $attribute === 'title' ?
                        Html::tag('CpLink',
                            $this->elementHtml->chipHtml($element, [
                                'context' => $renderContext,
                                'appearance' => 'plain',
                            ]),
                            ['href' => $element->getCpEditUrl()]
                        )
                        : (string) $this->attributeRenderer->render($element, $attribute),
                ]));

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

        return Inertia::render('content/Index', Arr::merge($context, [
            'status' => $request->input('status', ''),
            'source' => $this->resolveSource($elementType, $request->input('source', '*'), $renderContext)[1],
            'search' => $request->input('search'),
            'viewState' => $viewState,
            'statusOptions' => $statusOptions,
            'elements' => $elements,
            'contentHtml' => $contentHtml,
        ]));
    }
}
