<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

readonly class IndexController
{
    use RespondsWithFlash;

    public function __construct(
        private Volumes $volumes,
        private Folders $folders,
    ) {}

    public function __invoke(Request $request, ?string $defaultSource = null): View
    {
        $variables = [];

        if (! $defaultSource = $request->input('defaultSource', $defaultSource)) {
            return view('assets/_index', $variables);
        }

        $defaultSourcePath = Arr::whereNotEmpty(explode('/', (string) $defaultSource));
        $volume = $this->volumes->getVolumeByHandle(array_shift($defaultSourcePath));

        if (! $volume) {
            return view('assets/_index', $variables);
        }

        $variables['defaultSource'] = "volume:$volume->uid";

        if (empty($defaultSourcePath)) {
            return view('assets/_index', $variables);
        }

        $subfolder = $this->folders->findFolder([
            'volumeId' => $volume->id,
            'path' => sprintf('%s/', implode('/', $defaultSourcePath)),
        ]);

        if (! $subfolder) {
            return view('assets/_index', $variables);
        }

        $sourcePath = [];
        $folderChain = [];

        while ($subfolder) {
            array_unshift($folderChain, $subfolder);
            $subfolder = $subfolder->getParent();
        }

        foreach ($folderChain as $i => $folder) {
            if ($i < count($folderChain) - 1) {
                $folder->setHasChildren(true);
            }

            $sourcePath[] = $folder->getSourcePathInfo();
        }

        $variables['defaultSourcePath'] = $sourcePath;

        return view('assets/_index', $variables);
    }
}
