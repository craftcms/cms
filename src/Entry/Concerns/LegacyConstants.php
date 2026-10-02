<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Concerns;

if (! trait_exists(LegacyConstants::class)) {
    /**
     * @internal This is a hook for the yii2-adapter to define its constants.
     */
    trait LegacyConstants {}
}
