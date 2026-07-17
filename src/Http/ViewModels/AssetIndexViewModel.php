<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use Override;

/**
 * The Inertia payload for the asset index screen (`assets/Index`).
 *
 * A `defaultSource` path like `volumeHandle/sub/folder` selects the volume's
 * source and resolves the subfolder chain into `defaultSourcePath`.
 */
class AssetIndexViewModel extends ContentIndexViewModel
{
    /** @var array{0: Volume|null, 1: string[]}|null */
    private ?array $resolvedDefaultSource = null;

    public function __construct(
        ElementIndexRequest $request,
        private readonly ?string $defaultSource = null,
    ) {
        parent::__construct(Asset::class, $request);
    }

    public function defaultSource(): ?string
    {
        return $this->defaultSourceKey();
    }

    /** @return array<int, array|null>|null */
    public function defaultSourcePath(): ?array
    {
        $subfolder = $this->subfolder();

        if ($subfolder === null) {
            return null;
        }

        $folderChain = [];

        while ($subfolder) {
            array_unshift($folderChain, $subfolder);
            $subfolder = $subfolder->getParent();
        }

        $sourcePath = [];

        foreach ($folderChain as $i => $folder) {
            if ($i < count($folderChain) - 1) {
                $folder->setHasChildren(true);
            }

            $sourcePath[] = $folder->getSourcePathInfo();
        }

        return $sourcePath;
    }

    #[Override]
    protected function defaultSourceKey(): ?string
    {
        [$volume] = $this->resolveDefaultSource();

        return $volume === null ? null : "volume:{$volume->uid}";
    }

    /**
     * The volume named by the first `defaultSource` path segment, and the
     * remaining subfolder segments.
     *
     * @return array{0: Volume|null, 1: string[]}
     */
    private function resolveDefaultSource(): array
    {
        if ($this->resolvedDefaultSource !== null) {
            return $this->resolvedDefaultSource;
        }

        $segments = Arr::whereNotEmpty(explode('/', (string) $this->defaultSource));

        $volume = $segments === []
            ? null
            : Volumes::getVolumeByHandle(array_shift($segments));

        return $this->resolvedDefaultSource = [$volume, $segments];
    }

    private function subfolder(): ?VolumeFolder
    {
        [$volume, $segments] = $this->resolveDefaultSource();

        if ($volume === null || $segments === []) {
            return null;
        }

        return Folders::findFolder([
            'volumeId' => $volume->id,
            'path' => sprintf('%s/', implode('/', $segments)),
        ]);
    }
}
