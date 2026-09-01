<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Concerns;

if (! trait_exists(LegacyEventConstants::class)) {
    /**
     * @internal This is a hook for the yii2-adapter to define its constants.
     */
    trait LegacyEventConstants {}
}
