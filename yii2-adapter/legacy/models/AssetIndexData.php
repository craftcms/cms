<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Data\AssetIndexEntry} instead.
     */
    class AssetIndexData
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Data\AssetIndexEntry::class, AssetIndexData::class);
