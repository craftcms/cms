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

    public function __invoke(ElementIndexRequest $request, ?string $defaultSource = null): Response
    {
        // The CP asset index lists a volume/folder's subfolders alongside its
        // files; Asset::indexElements() only merges those in when `showFolders`
        // is set (matching the legacy index's request param). It reads the
        // global request, which is a separate instance from this FormRequest.
        request()->merge(['showFolders' => true]);

        return Inertia::render('assets/Index', new AssetIndexViewModel(
            $request,
            defaultSource: $request->input('defaultSource', $defaultSource),
        ));
    }
}
