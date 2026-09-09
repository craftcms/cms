<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\SlugConditionRule instead. */
class SlugConditionRule extends \CraftCms\Cms\Element\Conditions\SlugConditionRule
{
    use LegacyTextConditionRule;
}
