<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;

/**
 * Payload for the asset index screen (`assets/_index`).
 *
 * Resolves a `defaultSource` path like `volumeHandle/sub/folder` into the
 * source key and source-path chain the element index expects.
 */
class AssetIndexViewModel extends ViewModel
{
    /** @var array{0: Volume|null, 1: string[]}|null */
    private ?array $resolvedSource = null;

    public function __construct(
        private readonly ?string $defaultSource = null,
    ) {}

    public function defaultSource(): ?string
    {
        [$volume] = $this->resolveSource();

        return $volume === null ? null : "volume:{$volume->uid}";
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

    /**
     * The volume named by the first path segment, and the remaining
     * subfolder segments.
     *
     * @return array{0: Volume|null, 1: string[]}
     */
    private function resolveSource(): array
    {
        if ($this->resolvedSource !== null) {
            return $this->resolvedSource;
        }

        $segments = Arr::whereNotEmpty(explode('/', (string) $this->defaultSource));

        $volume = $segments === []
            ? null
            : Volumes::getVolumeByHandle(array_shift($segments));

        return $this->resolvedSource = [$volume, $segments];
    }

    private function subfolder(): ?VolumeFolder
    {
        [$volume, $segments] = $this->resolveSource();

        if ($volume === null || $segments === []) {
            return null;
        }

        return Folders::findFolder([
            'volumeId' => $volume->id,
            'path' => sprintf('%s/', implode('/', $segments)),
        ]);
    }
}
