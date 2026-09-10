<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Html;
use function CraftCms\Cms\t;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseNumberConditionRule */
trait LegacyNumberConditionRule
{
    use LegacyTextConditionRule {
        inputHtml as private textInputHtml;
        inputOptions as private textInputOptions;
    }

    protected function inputHtml(): string
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return $this->textInputHtml();
        }

        return Html::tag('div',
            Html::hiddenLabel(t('Min Value'), 'min') .
            FormFields::textHtml([
                'type' => $this->inputType(),
                'id' => 'min',
                'name' => 'value',
                'value' => $this->value,
                'autocomplete' => false,
                'class' => 'flex-grow flex-shrink',
            ]) .
            Html::tag('span', t('and')) .
            Html::hiddenLabel(t('Max Value'), 'max') .
            FormFields::textHtml([
                'type' => $this->inputType(),
                'id' => 'max',
                'name' => 'maxValue',
                'value' => $this->maxValue,
                'autocomplete' => false,
                'class' => 'flex-grow flex-shrink',
            ]) .
            Html::tag('craft-info-icon', t('The values are matched inclusively.')),
            ['class' => 'flex flex-center']
        );
    }

    /** @return array<string, mixed> */
    protected function inputOptions(): array
    {
        return array_merge($this->textInputOptions(), [
            'step' => $this->step ?? 'any',
        ]);
    }
}
