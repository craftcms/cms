<?php

namespace craft\elements\conditions\assets;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * "Has alternative text" condition rule for assets.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\HasAltConditionRule} instead.
     */
    class HasAltConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Conditions\HasAltConditionRule::class, HasAltConditionRule::class);
