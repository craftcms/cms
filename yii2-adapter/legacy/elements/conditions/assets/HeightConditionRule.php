<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\HeightConditionRule instead. */
class HeightConditionRule extends \CraftCms\Cms\Asset\Conditions\HeightConditionRule
{
    use LegacyNumberConditionRule;
}
