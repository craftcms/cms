<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Support\Facades\DB;
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

        $record = $volume->id
            ? DB::table(Table::VOLUMES)->select(['transformFs', 'transformSubpath'])->where('id', $volume->id)->first()
            : null;

        return $this->values[$volume] = new LegacyVolumeTransformValues(
            filesystem: isset($record->transformFs) ? (string) $record->transformFs : null,
            subpath: isset($record->transformSubpath) ? (string) $record->transformSubpath : '',
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
