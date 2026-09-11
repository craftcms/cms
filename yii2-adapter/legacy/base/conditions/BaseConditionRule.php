<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseConditionRule instead. */
abstract class BaseConditionRule extends \CraftCms\Cms\Condition\BaseConditionRule
{
    use LegacyConditionRuleForm;
}
