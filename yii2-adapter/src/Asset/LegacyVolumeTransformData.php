<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Filesystems;
use WeakMap;

class LegacyVolumeTransformData
{
    /** @var WeakMap<Volume, LegacyVolumeTransformValues> */
    private WeakMap $values;

    public function __construct()
    {
        $this->values = new WeakMap();
    }

    public function get(Volume $volume): LegacyVolumeTransformValues
    {
        if (isset($this->values[$volume])) {
            return $this->values[$volume];
        }

        $config = $volume->uid
            ? app(ProjectConfig::class)->get(ProjectConfig::PATH_VOLUMES . '.' . $volume->uid)
            : null;

        return $this->values[$volume] = new LegacyVolumeTransformValues(
            filesystem: is_array($config) && isset($config['transformFs']) ? (string) $config['transformFs'] : null,
            subpath: is_array($config) && isset($config['transformSubpath']) ? (string) $config['transformSubpath'] : '',
        );
    }

    public function setFilesystem(Volume $volume, ?string $handle): void
    {
        $this->get($volume)->filesystem = $this->normalizeStorageHandle($handle);
    }

    public function setSubpath(Volume $volume, string $subpath): void
    {
        $this->get($volume)->subpath = $subpath;
    }

    private function normalizeStorageHandle(?string $handle): ?string
    {
        if (
            !$handle
            || str_starts_with($handle, Volume::STORAGE_DISK_PREFIX)
            || str_contains($handle, '$')
            || str_starts_with($handle, '@')
            || Filesystems::getFilesystemByHandle($handle)
        ) {
            return $handle;
        }

        return app(FilesystemsService::class)->diskExists($handle)
            ? Volume::STORAGE_DISK_PREFIX . $handle
            : $handle;
    }
}

class LegacyVolumeTransformValues
{
    public function __construct(
        public ?string $filesystem,
        public string $subpath,
    ) {
    }
}
