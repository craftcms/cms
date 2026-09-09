<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseTextConditionRule */
trait LegacyTextConditionRule
{
    use LegacyConditionRuleForm;

    protected function inputHtml(): string
    {
        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            return FormFields::selectizeHtml($this->inputOptions());
        }

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), 'value') .
            FormFields::textHtml($this->inputOptions());
    }

    /**
     * Returns the input options that should be used.
     *
     * @return array<string, mixed>
     */
    protected function inputOptions(): array
    {
        $defaults = [
            'id' => 'value' . mt_rand(),
            'name' => 'value',
            'class' => 'flex-grow flex-shrink',
        ];

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            $values = Json::decodeIfJson($this->value);
            $values = is_array($values) ? array_values($values) : [];

            return [...$defaults, ...[
                'values' => $values,
                'options' => array_map(fn($v) => ['value' => $v, 'label' => $v], $values),
                'multi' => true,
                'allowEmptyOption' => true,
                'selectizeOptions' => [
                    'create' => true,
                    'persist' => false,
                    'createOnBlur' => true,
                ],
            ]];
        }

        return [
            ...$defaults,
            ...[
                'type' => $this->inputType(),
                'value' => $this->value,
                'autocomplete' => false,
            ],
        ];
    }
}
