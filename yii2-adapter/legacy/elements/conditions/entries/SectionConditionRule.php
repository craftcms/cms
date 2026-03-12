<?php

namespace craft\elements\conditions\entries;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\SectionConditionRule} instead.
 */
class SectionConditionRule extends \CraftCms\Cms\Entry\Conditions\SectionConditionRule
{
    use \craft\base\LegacyEventConstants;
}
