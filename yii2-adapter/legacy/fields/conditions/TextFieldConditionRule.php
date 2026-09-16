<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\TextFieldConditionRule instead. */
class TextFieldConditionRule extends \CraftCms\Cms\Field\Conditions\TextFieldConditionRule
{
    use LegacyTextConditionRule;
}
