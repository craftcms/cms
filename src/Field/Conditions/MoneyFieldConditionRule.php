<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Money as MoneyHelper;
use Money\Currency;
use Money\Money as MoneyLibrary;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

class MoneyFieldConditionRule extends BaseNumberConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /** @return list<string> */
    #[Override]
    protected function operators(): array
    {
        return array_filter(
            parent::operators(),
            // Remove IN/NOT IN operators as they don't fit with the implementation of money inputs
            fn (string $operator) => ! in_array($operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  bool  $safeOnly
     */
    #[Override]
    public function setAttributes($values, $safeOnly = true): void
    {
        // Hold setting of the value attribute until we have all the info we need
        if (isset($values['value']) && is_array($values['value'])) {
            $value = Arr::pull($values, 'value');
        }

        if (isset($values['maxValue']) && is_array($values['maxValue'])) {
            $maxValue = Arr::pull($values, 'maxValue');
        }

        parent::setAttributes($values);

        $field = $this->field();

        if (! $field instanceof Money) {
            throw new RuntimeException;
        }

        if (isset($value, $this->_fieldUid)) {
            $value['currency'] ??= $field->currency;

            $this->value = MoneyHelper::toDecimal(MoneyHelper::toMoney($value));
        }

        if (isset($maxValue, $this->_fieldUid)) {
            $maxValue['currency'] ??= $field->currency;
            $this->maxValue = MoneyHelper::toDecimal(MoneyHelper::toMoney($maxValue));
        }
    }

    #[Override]
    protected function inputHtml(): string
    {
        $field = $this->field();

        if (! $field instanceof Money) {
            throw new RuntimeException;
        }

        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if ($this->operator === self::OPERATOR_BETWEEN) {
            $maxValue = is_numeric($this->maxValue) ? MoneyHelper::toNumber(MoneyHelper::toMoney(['value' => $this->maxValue, 'currency' => $field->currency])) : $this->maxValue;

            return Html::tag('div',
                Html::hiddenLabel(t('Min Value'), 'min').
                // Min value (value) input
                FormFields::moneyInputHtml($this->inputOptions()).
                Html::tag('span', t('and')).
                Html::hiddenLabel(t('Max Value'), 'max').
                // Max value input
                FormFields::moneyInputHtml(array_merge(
                    $this->inputOptions(),
                    ['id' => 'maxValue', 'name' => 'maxValue', 'value' => $maxValue]
                )).
                Html::tag('craft-info-icon', t('The values are matched inclusively.')),
                ['class' => 'flex flex-center']
            );
        }

        return FormFields::moneyInputHtml($this->inputOptions());
    }

    /** @return array{type: 'text', id: 'value', name: 'value', value: string|false, autocomplete: false, currency: string, currencyLabel: string, showCurrency: bool, decimals: int, defaultValue: string|false|null, describedBy: string|null, field: Money, showClear: false} */
    #[Override]
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

    protected function elementQueryParam(): ?string
    {
        if (! $this->field() instanceof Money) {
            return null;
        }

        return $this->paramValue();
    }

    /** @param MoneyLibrary|float|int|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if (! $this->field() instanceof Money) {
            return true;
        }

        if ($value instanceof MoneyLibrary) {
            $value = (float) $value->getAmount();
        }

        return $this->matchValue($value);
    }
}
