<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Deprecated;

if (! class_exists(LegacyConstants::class)) {
    /**
     * @internal
     */
    #[Deprecated(message: 'This is a hook for the yii2-adapter to define its constants.', since: '6.0.0')]
    trait LegacyConstants {}
}
