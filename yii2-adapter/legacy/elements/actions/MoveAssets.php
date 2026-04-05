<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Actions\MoveAssets} instead.
     */
    class MoveAssets extends \CraftCms\Cms\Asset\Actions\MoveAssets
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Actions\MoveAssets::class, MoveAssets::class);
