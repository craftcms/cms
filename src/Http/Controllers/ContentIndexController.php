<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Element\ElementIndexParams;
use CraftCms\Cms\Element\ElementIndexService;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function CraftCms\Cms\t;

/**
 * @TODO Make this more generic, for now I'm just replacing entries
 */
class ContentIndexController
{
    public function __construct(
        private readonly PrepareElementIndexVariables $prepareElementIndexVariables,
        private readonly ElementIndexService $elementIndexService,
        private readonly ElementSources $elementSources,
    ) {}

    public function __invoke(Request $request, string $page, ?string $sectionHandle = null)
    {
        $elementType = Entry::class;
        $context = [
            'page' => $page,
            'sectionHandle' => $sectionHandle ?? '',
            'elementType' => $elementType,
        ];

        ($this->prepareElementIndexVariables)($context);

        // Determine the initial source key from the resolved sources
        $sourceKey = $request->input('source');
        if (! empty($context['sources'])) {
            // If a section handle is provided, find the matching source
            if ($sectionHandle) {
                foreach ($context['sources'] as $source) {
                    if (isset($source['key']) && str_contains($source['key'], $sectionHandle)) {
                        $sourceKey = $source['key'];
                        break;
                    }
                }
            }

            // Fall back to the first source key
            if ($sourceKey === null) {
                $sourceKey = $context['sources'][0]['key'] ?? null;
            }
        }

        $sort = ! empty($request->array('sort')) ? $request->array('sort') : [
            ['field' => 'dateCreated', 'direction' => 'desc'],
        ];

        $criteria = [];
        if ($request->has('status')) {
            $criteria['status'] = $request->input('status');
        }

        if ($request->has('search')) {
            $criteria['search'] = $request->input('search');
        }

        $params = ElementIndexParams::fromContext(
            elementType: $elementType,
            sourceKey: $sourceKey,
            criteria: $criteria,
            elementSources: $this->elementSources,
            page: max(1, $request->integer('page', 1)),
            perPage: max(1, $request->integer('per_page', 100)),
            sort: $sort,
        );

        $elementStatuses = $elementType::statuses();
        $statusOptions = collect($elementStatuses)
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->prepend(['label' => t('All'), 'value' => ''])
            ->values()
            ->all();

        return Inertia::render('content/Index', Arr::merge($context, [
            'source' => $this->elementSources->findSource($context['elementType'], $sourceKey, $context['context']),
            'status' => $request->input('status', ''),
            'search' => $request->input('search'),
            'statusOptions' => $statusOptions,
            'viewMode' => $request->input('viewMode', 'table'),
            'contentHtml' => $this->elementIndexService->getElementsHtml($params)['html'],
        ]));
    }
}
