<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseNumberConditionRule instead. */
abstract class BaseNumberConditionRule extends \CraftCms\Cms\Condition\BaseNumberConditionRule
{
    use LegacyNumberConditionRule;
}
