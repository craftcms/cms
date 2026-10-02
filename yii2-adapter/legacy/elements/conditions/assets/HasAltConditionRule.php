<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\HasAltConditionRule instead. */
class HasAltConditionRule extends \CraftCms\Cms\Asset\Conditions\HasAltConditionRule
{
    use LegacyLightswitchConditionRule;
}
