<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cp\SiteSwitcher;
use CraftCms\Cms\Http\Controllers\Concerns\RedirectsToShownSource;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\EntryIndexViewModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

readonly class ContentIndexController
{
    use RedirectsToShownSource;

    public function __invoke(ElementIndexRequest $request, string $page, ?string $sectionHandle = null): Response|RedirectResponse
    {
        $viewModel = new EntryIndexViewModel(
            request: $request,
            page: $page,
            sectionHandle: $sectionHandle,
        );

        if ($viewModel->showSiteMenu()) {
            app(SiteSwitcher::class)->scopeToSite();
        }

        return $this->shownSourceRedirect($request, $viewModel, $sectionHandle !== null && $sectionHandle !== '')
            ?? Inertia::render('content/Index', [$viewModel]);
    }
}
