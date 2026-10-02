<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

if (! trait_exists(LegacyFieldConstants::class)) {
    /**
     * @internal This is a hook for the yii2-adapter to define its constants.
     */
    trait LegacyFieldConstants {}
}
