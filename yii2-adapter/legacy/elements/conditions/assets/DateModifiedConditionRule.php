<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule instead. */
class DateModifiedConditionRule extends \CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule
{
    use LegacyDateRangeConditionRule;
}
