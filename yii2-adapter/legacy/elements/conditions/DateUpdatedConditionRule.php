<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule instead. */
class DateUpdatedConditionRule extends \CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule
{
    use LegacyDateRangeConditionRule;
}
