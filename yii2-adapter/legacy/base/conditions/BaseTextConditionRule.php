<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseTextConditionRule instead. */
abstract class BaseTextConditionRule extends \CraftCms\Cms\Condition\BaseTextConditionRule
{
    use LegacyTextConditionRule;
}
