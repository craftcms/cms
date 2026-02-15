<?php

namespace craft\elements\conditions\assets;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Asset savable condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\SavableConditionRule} instead.
     */
    class SavableConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Conditions\SavableConditionRule::class, SavableConditionRule::class);
