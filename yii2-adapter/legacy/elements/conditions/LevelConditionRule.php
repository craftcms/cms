<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\LevelConditionRule instead. */
class LevelConditionRule extends \CraftCms\Cms\Element\Conditions\LevelConditionRule
{
    use LegacyNumberConditionRule;
}
