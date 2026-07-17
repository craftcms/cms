<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\ViewModels\AssetIndexViewModel;
use Inertia\Inertia;
use Inertia\Response;

readonly class IndexController
{
    use RespondsWithFlash;

    public function __invoke(ElementIndexRequest $request, ?string $page = null, ?string $defaultSource = null): Response
    {
        return Inertia::render('assets/Index', new AssetIndexViewModel(
            $request,
            page: $page,
            defaultSource: $request->input('defaultSource', $defaultSource),
        ));
    }
}
