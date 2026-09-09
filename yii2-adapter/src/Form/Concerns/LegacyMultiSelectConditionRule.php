<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Html;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseMultiSelectConditionRule */
trait LegacyMultiSelectConditionRule
{
    use LegacyConditionRuleForm;

    protected function inputHtml(): string
    {
        if (!in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            return '';
        }

        $multiSelectId = 'multiselect';

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId) .
            FormFields::selectizeHtml([
                'id' => $multiSelectId,
                'class' => 'flex-grow',
                'name' => 'values',
                'values' => $this->getValues(),
                'options' => $this->options(),
                'multi' => true,
            ]);
    }
}
