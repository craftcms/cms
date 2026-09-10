<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyElementSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseElementSelectConditionRule instead. */
abstract class BaseElementSelectConditionRule extends \CraftCms\Cms\Condition\BaseElementSelectConditionRule
{
    use LegacyElementSelectConditionRule;
}
