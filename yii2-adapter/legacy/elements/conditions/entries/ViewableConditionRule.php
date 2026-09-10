<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyLightswitchConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\ViewableConditionRule instead. */
class ViewableConditionRule extends \CraftCms\Cms\Entry\Conditions\ViewableConditionRule
{
    use LegacyLightswitchConditionRule;
}
