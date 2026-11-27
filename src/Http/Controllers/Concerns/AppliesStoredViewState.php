<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Concerns;

use Closure;
use CraftCms\Cms\Element\ElementIndexViewStates;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use CraftCms\Cms\Support\Str;
use Inertia\Inertia;
use Inertia\Support\Header;

/**
 * Renders an index page load that names no view mode for the session's view
 * state for its source (see
 * {@see ContentIndexViewModel::storedViewStateQuery()}).
 *
 * Without a view mode, the page comes without rows, and the index requests
 * them once it has read the view state from local storage. With the stored
 * state, the response has them, so navigating to an index, and prefetching it
 * from the nav, shows its rows straight away. The page's URL names the params
 * the rows were rendered for, as if the request had, and
 * `sessionViewStateParams` lists them, so the index compares them with local
 * storage rather than adopting them as the link's choice.
 */
trait AppliesStoredViewState
{
    /**
     * @template T of ContentIndexViewModel
     *
     * @param  T  $viewModel
     * @param  Closure(): T  $makeViewModel  Builds the view model again, for the request with the params added.
     * @return T
     */
    private function withStoredViewState(
        ElementIndexRequest $request,
        ContentIndexViewModel $viewModel,
        Closure $makeViewModel,
    ): ContentIndexViewModel {
        if (
            ! $request->isMethod('GET') ||
            $request->has('viewMode') ||
            $request->hasHeader(Header::PARTIAL_COMPONENT)
        ) {
            return $viewModel;
        }

        $query = $viewModel->storedViewStateQuery(app(ElementIndexViewStates::class));

        if ($query === null) {
            return $viewModel;
        }

        // Params the URL names win, as they do in the index's own restore.
        $query = array_diff_key($query, $request->query());

        $url = Str::after($request->fullUrlWithQuery($query), $request->getSchemeAndHttpHost());
        Inertia::resolveUrlUsing(fn (): string => $url);

        // `DisplayedInIndex` reads `collapsedElementIds` from the global request.
        $request->merge($query);
        request()->merge($query);

        $viewModel = $makeViewModel();
        $viewModel->sessionViewStateParams = array_keys($query);

        return $viewModel;
    }
}
