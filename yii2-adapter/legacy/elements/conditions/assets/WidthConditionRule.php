<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\WidthConditionRule instead. */
class WidthConditionRule extends \CraftCms\Cms\Asset\Conditions\WidthConditionRule
{
    use LegacyNumberConditionRule;
}
