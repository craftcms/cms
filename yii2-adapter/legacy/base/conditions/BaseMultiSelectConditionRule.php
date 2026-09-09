<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseMultiSelectConditionRule instead. */
abstract class BaseMultiSelectConditionRule extends \CraftCms\Cms\Condition\BaseMultiSelectConditionRule
{
    use LegacyMultiSelectConditionRule;
}
