<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyNumberConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\NumberFieldConditionRule instead. */
class NumberFieldConditionRule extends \CraftCms\Cms\Field\Conditions\NumberFieldConditionRule
{
    use LegacyNumberConditionRule;
}
