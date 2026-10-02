<?php

declare(strict_types=1);

namespace craft\elements\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Element\Conditions\LanguageConditionRule instead. */
class LanguageConditionRule extends \CraftCms\Cms\Element\Conditions\LanguageConditionRule
{
    use LegacyMultiSelectConditionRule;
}
