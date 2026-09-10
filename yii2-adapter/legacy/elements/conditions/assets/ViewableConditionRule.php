<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\ViewableConditionRule instead. */
class ViewableConditionRule extends \CraftCms\Cms\Asset\Conditions\ViewableConditionRule
{
    use LegacyLightswitchConditionRule;
}
