<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementIndexReturnUrl;
use CraftCms\Cms\Http\Controllers\Concerns\RedirectsToShownSource;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\UserIndexViewModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @since 6.0.0
 */
readonly class IndexController
{
    use AuthorizesRequests;
    use RedirectsToShownSource;

    public function __invoke(ElementIndexRequest $request, ?string $slug = null): Response|RedirectResponse
    {
        $this->authorize('viewUsers');

        Edition::require(Edition::Team);

        $viewModel = new UserIndexViewModel(
            $request,
            slug: $slug,
        );

        if ($redirect = $this->shownSourceRedirect($request, $viewModel, $slug !== null && $slug !== '')) {
            return $redirect;
        }

        ElementIndexReturnUrl::remember($viewModel->elementType(), $request);

        return Inertia::render('users/Index', [$viewModel]);
    }
}
