<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseDateRangeConditionRule instead. */
abstract class BaseDateRangeConditionRule extends \CraftCms\Cms\Condition\BaseDateRangeConditionRule
{
    use LegacyDateRangeConditionRule;
}
