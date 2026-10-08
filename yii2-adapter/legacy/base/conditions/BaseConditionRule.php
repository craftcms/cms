<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Ui\Concerns\LegacyConditionRuleUi;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseConditionRule instead. */
abstract class BaseConditionRule extends \CraftCms\Cms\Condition\BaseConditionRule
{
    use LegacyConditionRuleUi;
}
