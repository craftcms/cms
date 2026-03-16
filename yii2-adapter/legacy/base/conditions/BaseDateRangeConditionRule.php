<?php

namespace craft\base\conditions;

use craft\fields\Date;

/**
 * BaseDateRangeConditionRule provides a base implementation for condition rules that are composed of date range inputs.
 *
 * @property string|null $startDate
 * @property string|null $endDate
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseDateRangeConditionRule} instead.
 */
abstract class BaseDateRangeConditionRule extends \CraftCms\Cms\Condition\BaseDateRangeConditionRule
{
    use \craft\base\LegacyEventConstants;
}
