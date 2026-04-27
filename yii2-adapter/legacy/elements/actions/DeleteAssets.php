<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Actions\DeleteAssets} instead.
     */
    class DeleteAssets extends \CraftCms\Cms\Asset\Actions\DeleteAssets
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Actions\DeleteAssets::class, DeleteAssets::class);
