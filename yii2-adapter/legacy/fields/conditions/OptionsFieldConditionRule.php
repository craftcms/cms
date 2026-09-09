<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;
use RuntimeException;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule instead. */
class OptionsFieldConditionRule extends \CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule
{
    use LegacyMultiSelectConditionRule {
        inputHtml as private baseInputHtml;
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof BaseOptionsField) {
            throw new RuntimeException();
        }

        return $this->baseInputHtml();
    }
}
