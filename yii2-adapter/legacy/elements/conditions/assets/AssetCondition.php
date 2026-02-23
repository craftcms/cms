<?php

namespace craft\elements\conditions\assets;

use CraftCms\Cms\Element\Conditions\ElementCondition;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Asset query condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\AssetCondition} instead.
     */
    class AssetCondition extends ElementCondition
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Conditions\AssetCondition::class, AssetCondition::class);
