<?php

namespace craft\elements\conditions;

/**
 * Element has URL condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\HasUrlConditionRule} instead.
 */
class HasUrlConditionRule extends \CraftCms\Cms\Element\Conditions\HasUrlConditionRule
{
    use \craft\base\LegacyEventConstants;
}
