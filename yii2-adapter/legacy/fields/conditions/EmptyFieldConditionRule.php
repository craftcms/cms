<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule instead. */
class EmptyFieldConditionRule extends \CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule
{
    use LegacyConditionRuleForm;
}
