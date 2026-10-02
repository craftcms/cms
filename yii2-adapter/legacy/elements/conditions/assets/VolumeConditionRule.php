<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\VolumeConditionRule instead. */
class VolumeConditionRule extends \CraftCms\Cms\Asset\Conditions\VolumeConditionRule
{
    use LegacyMultiSelectConditionRule;
}
