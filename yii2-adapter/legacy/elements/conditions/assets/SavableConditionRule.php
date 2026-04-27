<?php

namespace craft\elements\conditions\assets;

/**
 * Asset savable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\SavableConditionRule} instead.
 */
class SavableConditionRule extends \CraftCms\Cms\Asset\Conditions\SavableConditionRule
{
    use \craft\base\LegacyEventConstants;
}
