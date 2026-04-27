<?php

namespace craft\elements\conditions\assets;

/**
 * Asset viewable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Conditions\ViewableConditionRule} instead.
 */
class ViewableConditionRule extends \CraftCms\Cms\Asset\Conditions\ViewableConditionRule
{
    use \craft\base\LegacyEventConstants;
}
