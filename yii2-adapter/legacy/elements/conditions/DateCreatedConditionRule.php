<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\DateCreatedConditionRule instead. */
class DateCreatedConditionRule extends \CraftCms\Cms\Element\Conditions\DateCreatedConditionRule
{
    use LegacyDateRangeConditionRule;
}
