<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\ViewModels\AssetIndexViewModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\cp_url;

readonly class IndexController
{
    use RespondsWithFlash;

    public function __invoke(ElementIndexRequest $request, ?string $defaultSource = null): Response|RedirectResponse
    {
        // The CP asset index lists a volume/folder's subfolders alongside its
        // files; Asset::indexElements() only merges those in when `showFolders`
        // is set (matching the legacy index's request param). It reads the
        // global request, which is a separate instance from this FormRequest.
        request()->merge(['showFolders' => true]);

        $viewModel = new AssetIndexViewModel(
            $request,
            defaultSource: $request->input('defaultSource', $defaultSource),
        );

        if ($redirect = $this->firstSourceRedirect($request, $viewModel, $defaultSource)) {
            return $redirect;
        }

        return Inertia::render('assets/Index', [$viewModel]);
    }

    /**
     * Sends the bare index to the URL of the source it shows.
     *
     * With no source named, the index shows the first one it lists, but at a
     * URL that doesn't say which. The nav can then only guess, and a source
     * addressed by query alone (Temporary Uploads) matched that URL and took
     * the selection.
     */
    private function firstSourceRedirect(
        ElementIndexRequest $request,
        AssetIndexViewModel $viewModel,
        ?string $defaultSource,
    ): ?RedirectResponse {
        if ($defaultSource !== null || $request->filled('defaultSource') || $request->filled('source')) {
            return null;
        }

        $source = $viewModel->source();
        $uri = $source === null ? null : Asset::sourceCpUri($source);

        if ($uri === null) {
            return null;
        }

        return redirect(cp_url($uri, $request->query() ?: null));
    }
}
