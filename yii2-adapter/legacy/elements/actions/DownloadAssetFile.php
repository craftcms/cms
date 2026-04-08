<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Actions\DownloadAssetFile} instead.
     */
    class DownloadAssetFile extends \CraftCms\Cms\Asset\Actions\DownloadAssetFile
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Actions\DownloadAssetFile::class, DownloadAssetFile::class);
