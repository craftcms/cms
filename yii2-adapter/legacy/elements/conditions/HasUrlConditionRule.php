<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\HasUrlConditionRule instead. */
class HasUrlConditionRule extends \CraftCms\Cms\Element\Conditions\HasUrlConditionRule
{
    use LegacyLightswitchConditionRule;
}
