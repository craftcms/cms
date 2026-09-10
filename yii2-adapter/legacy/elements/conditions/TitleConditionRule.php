<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\TitleConditionRule instead. */
class TitleConditionRule extends \CraftCms\Cms\Element\Conditions\TitleConditionRule
{
    use LegacyTextConditionRule;
}
