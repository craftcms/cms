<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\HasDescendantsRule instead. */
class HasDescendantsRule extends \CraftCms\Cms\Element\Conditions\HasDescendantsRule
{
    use LegacyLightswitchConditionRule;
}
