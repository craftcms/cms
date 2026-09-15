<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseLightswitchConditionRule instead. */
abstract class BaseLightswitchConditionRule extends \CraftCms\Cms\Condition\BaseLightswitchConditionRule
{
    use LegacyLightswitchConditionRule;
}
