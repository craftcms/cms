<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Yii2Adapter\Ui\Concerns\LegacyConditionRuleUi;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule instead. */
class EmptyFieldConditionRule extends \CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule
{
    use LegacyConditionRuleUi;
}
