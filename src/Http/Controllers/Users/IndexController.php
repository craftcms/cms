<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\UserIndexViewModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

readonly class IndexController
{
    use AuthorizesRequests;

    public function __invoke(ElementIndexRequest $request, ?string $slug = null): Response
    {
        $this->authorize('viewUsers');

        Edition::require(Edition::Team);

        return Inertia::render('users/Index', new UserIndexViewModel(
            $request,
            slug: $slug,
        ));
    }
}
