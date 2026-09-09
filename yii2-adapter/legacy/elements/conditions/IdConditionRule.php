<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\IdConditionRule instead. */
class IdConditionRule extends \CraftCms\Cms\Element\Conditions\IdConditionRule
{
    use LegacyNumberConditionRule;
}
