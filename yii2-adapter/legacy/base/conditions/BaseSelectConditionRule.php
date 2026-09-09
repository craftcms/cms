<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacySelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseSelectConditionRule instead. */
abstract class BaseSelectConditionRule extends \CraftCms\Cms\Condition\BaseSelectConditionRule
{
    use LegacySelectConditionRule;
}
