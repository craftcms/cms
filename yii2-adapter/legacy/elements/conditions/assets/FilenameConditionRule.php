<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\FilenameConditionRule instead. */
class FilenameConditionRule extends \CraftCms\Cms\Asset\Conditions\FilenameConditionRule
{
    use LegacyTextConditionRule;
}
