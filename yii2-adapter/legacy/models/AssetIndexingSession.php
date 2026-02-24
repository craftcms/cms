<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Data\IndexingSession} instead.
     */
    class AssetIndexingSession
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Data\IndexingSession::class, AssetIndexingSession::class);
