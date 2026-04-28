<?php

namespace craft\elements\conditions;

/**
 * Element status condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\StatusConditionRule} instead.
 */
class StatusConditionRule extends \CraftCms\Cms\Element\Conditions\StatusConditionRule
{
    use \craft\base\LegacyEventConstants;
}
