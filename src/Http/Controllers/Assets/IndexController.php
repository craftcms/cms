<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\ViewModels\AssetIndexViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

readonly class IndexController
{
    use RespondsWithFlash;

    public function __invoke(Request $request, ?string $defaultSource = null): View
    {
        return view('assets/_index', new AssetIndexViewModel(
            $request->input('defaultSource', $defaultSource),
        ));
    }
}
