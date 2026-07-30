<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @TODO Make this more generic, for now I'm just replacing entries
 */
readonly class ContentIndexController
{
    public function __construct(
        private PrepareElementIndexVariables $prepareElementIndexVariables,
        private PrepareElementToolbarVariables $prepareElementToolbarVariables,
        private PrepareElementSourcesVariables $prepareElementSourcesVariables,
    ) {}

    public function __invoke(ElementIndexRequest $request, string $page, ?string $sectionHandle = null): Response
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

        $viewModel = new ContentIndexViewModel(
            elementType: $elementType,
            request: $request,
            sectionHandle: $sectionHandle,
            elementStatuses: $context['elementStatuses'] ?? [],
        );

        // Plain merge, view model last: its payload keys override any
        // same-named context vars from the prepare hooks (e.g. the legacy
        // assoc-keyed `tableColumns`) — a recursive merge would interleave
        // them into a JSON object instead of a list.
        return Inertia::render('content/Index', array_merge($context, $viewModel->toArray()));
    }
}
