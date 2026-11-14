<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cp\SiteSwitcher;
use CraftCms\Cms\Http\Controllers\Concerns\RedirectsToShownSource;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\EntryIndexViewModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @since 6.0.0
 */
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

        return $this->singleRedirect($request, $viewModel)
            ?? $this->shownSourceRedirect($request, $viewModel, $sectionHandle !== null && $sectionHandle !== '')
            ?? Inertia::render('content/Index', [$viewModel]);
    }

    /**
     * Sends a single's source, and a page that opens on one, to the single's
     * entry: its index would only list the entry the person came to edit.
     *
     * A source named by `?source=` keeps its index, so the listing stays
     * reachable.
     */
    private function singleRedirect(ElementIndexRequest $request, EntryIndexViewModel $viewModel): ?RedirectResponse
    {
        if ($request->filled('source') || ! $request->isMethod('GET')) {
            return null;
        }

        $key = $viewModel->source()['key'] ?? null;

        if (! is_string($key) || ! preg_match('/^section:(.+)$/', $key, $matches)) {
            return null;
        }

        $section = Sections::getSectionByUid($matches[1]);

        if ($section?->type !== SectionType::Single) {
            return null;
        }

        $url = (Entries::getSingleEntriesByHandle([$section->handle])[$section->handle] ?? null)?->getCpEditUrl();

        return $url !== null ? redirect($url) : null;
    }
}
