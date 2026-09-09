<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Money as MoneyHelper;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;
use Money\Currency;
use Money\Money as MoneyLibrary;

use RuntimeException;
use function CraftCms\Cms\t;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule instead. */
class MoneyFieldConditionRule extends \CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule
{
    public function getForm(): Form
    {
        return app(LegacyConditionRuleForm::class)->capture($this, $this->getHtml(...));
    }

    public function getHtml(): string
    {
        return app(LegacyConditionRuleForm::class)->render(Form::make($this->operatorNodes()), $this->inputHtml());
    }

    protected function inputHtml(): string
    {
        $field = $this->field();

        if (!$field instanceof Money) {
            throw new RuntimeException();
        }

        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if ($this->operator === self::OPERATOR_BETWEEN) {
            $maxValue = is_numeric($this->maxValue) ? MoneyHelper::toNumber(MoneyHelper::toMoney(['value' => $this->maxValue, 'currency' => $field->currency])) : $this->maxValue;

            return Html::tag('div',
                Html::hiddenLabel(t('Min Value'), 'min') .
                // Min value (value) input
                FormFields::moneyInputHtml($this->inputOptions()) .
                Html::tag('span', t('and')) .
                Html::hiddenLabel(t('Max Value'), 'max') .
                // Max value input
                FormFields::moneyInputHtml(array_merge(
                    $this->inputOptions(),
                    ['id' => 'maxValue', 'name' => 'maxValue', 'value' => $maxValue]
                )) .
                Html::tag('craft-info-icon', t('The values are matched inclusively.')),
                ['class' => 'flex flex-center']
            );
        }

        return FormFields::moneyInputHtml($this->inputOptions());
    }

    /** @return array{type: 'text', id: 'value', name: 'value', value: string|false, autocomplete: false, currency: string, currencyLabel: string, showCurrency: bool, decimals: int, defaultValue: string|false|null, describedBy: string|null, field: Money, showClear: false} */
    protected function inputOptions(): array
    {
        /** @var Money $field */
        $field = $this->field();
        $defaultValue = null;
        if ($field->defaultValue !== null) {
            $defaultValue = MoneyHelper::toNumber(new MoneyLibrary($field->defaultValue, new Currency($field->currency)));
        }

        $value = is_numeric($this->value) ? MoneyHelper::toNumber(MoneyHelper::toMoney(['value' => $this->value, 'currency' => $field->currency])) : $this->value;

        return [
            'type' => 'text',
            'id' => 'value',
            'name' => 'value',
            'value' => $value,
            'autocomplete' => false,
            'currency' => $field->currency,
            'currencyLabel' => $field->currencyLabel(),
            'showCurrency' => $field->showCurrency,
            'decimals' => $field->subunits(),
            'defaultValue' => $defaultValue,
            'describedBy' => $field->describedBy,
            'field' => $field,
            'showClear' => false,
        ];
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        if (static::class === self::class) {
            $config['class'] = \CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule::class;
        }

        return $config;
    }
}
