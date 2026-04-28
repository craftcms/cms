<?php

namespace craft\elements\conditions\assets;

/**
 * "Has alternative text" condition rule for assets.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\HasAltConditionRule} instead.
 */
class HasAltConditionRule extends \CraftCms\Cms\Asset\Conditions\HasAltConditionRule
{
    use \craft\base\LegacyEventConstants;
}
