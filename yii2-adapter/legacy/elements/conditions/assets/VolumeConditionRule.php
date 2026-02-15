<?php

namespace craft\elements\conditions\assets;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Asset volume condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\VolumeConditionRule} instead.
     */
    class VolumeConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Conditions\VolumeConditionRule::class, VolumeConditionRule::class);
