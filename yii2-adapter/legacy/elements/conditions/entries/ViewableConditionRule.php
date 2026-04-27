<?php

namespace craft\elements\conditions\entries;

/**
 * Entry viewable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\ViewableConditionRule} instead.
 */
class ViewableConditionRule extends \CraftCms\Cms\Entry\Conditions\ViewableConditionRule
{
    use \craft\base\LegacyEventConstants;
}
