<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Html;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseSelectConditionRule */
trait LegacySelectConditionRule
{
    use LegacyConditionRuleForm;

    protected function inputHtml(): string
    {
        $selectId = 'select';

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $selectId) .
            FormFields::selectHtml([
                'id' => $selectId,
                'name' => 'value',
                'options' => $this->options(),
                'value' => $this->value,
            ]);
    }
}
