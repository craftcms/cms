<?php

declare(strict_types=1);

namespace craft\elements\conditions\users;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyDateRangeConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\User\Conditions\LastLoginDateConditionRule instead. */
class LastLoginDateConditionRule extends \CraftCms\Cms\User\Conditions\LastLoginDateConditionRule
{
    use LegacyDateRangeConditionRule;
}
