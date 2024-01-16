<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseNumberConditionRule;
use craft\fields\Money;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\MoneyHelper;
use Money\Currency;
use Money\Money as MoneyLibrary;

/**
 * Money field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class MoneyFieldConditionRule extends BaseNumberConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @var mixed
     */
    private mixed $_tempValue;

    /**
     * @var mixed
     */
    private mixed $_tempMaxValue;

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        // Hold setting of the value attribute until we have all the info we need
        if (isset($values['value']) && is_array($values['value'])) {
            $this->_tempValue = $values['value'];
            unset($values['value']);
        }

        if (isset($values['maxValue']) && is_array($values['maxValue'])) {
            $this->_tempMaxValue = $values['maxValue'];
            unset($values['maxValue']);
        }

        parent::setAttributes($values, $safeOnly);

        /** @var Money $field */
        $field = $this->field();

        if (isset($this->_tempValue) && is_array($this->_tempValue) && isset($this->_fieldUid)) {
            if (!isset($this->_tempValue['currency'])) {
                $this->_tempValue['currency'] = $field->currency;
            }

            $this->_tempValue = MoneyHelper::toMoney($this->_tempValue);
            $this->value = MoneyHelper::toDecimal($this->_tempValue);
            $this->_tempValue = null;
        }

        if (isset($this->_tempMaxValue) && is_array($this->_tempMaxValue) && isset($this->_fieldUid)) {
            if (!isset($this->_tempMaxValue['currency'])) {
                $this->_tempMaxValue['currency'] = $field->currency;
            }

            $this->_tempMaxValue = MoneyHelper::toMoney($this->_tempMaxValue);
            $this->maxValue = MoneyHelper::toDecimal($this->_tempMaxValue);
            $this->_tempMaxValue = null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if ($this->operator === self::OPERATOR_BETWEEN) {
            return Html::tag('div',
                Html::hiddenLabel(Craft::t('app', 'Min Value'), 'min') .
                // Min value (value) input
                Cp::moneyInputHtml($this->inputOptions()) .
                Html::tag('span', Craft::t('app', 'and')) .
                Html::hiddenLabel(Craft::t('app', 'Max Value'), 'max') .
                // Max value input
                Cp::moneyInputHtml(array_merge(
                    $this->inputOptions(),
                    ['id' => 'maxValue', 'name' => 'maxValue', 'value' => $this->maxValue]
                )) .
                Html::tag('span', Craft::t('app', 'The values are matched inclusively.'), ['class' => 'info']),
                ['class' => 'flex flex-center']
            );
        }

        return Cp::moneyInputHtml($this->inputOptions());
    }

    /**
     * @inheritdoc
     */
    protected function inputOptions(): array
    {
        /** @var Money $field */
        $field = $this->field();
        $defaultValue = null;
        if ($field->defaultValue !== null) {
            $defaultValue = MoneyHelper::toNumber(new MoneyLibrary($field->defaultValue, new Currency($field->currency)));
        }

        return [
            'type' => 'text',
            'id' => 'value',
            'name' => 'value',
            'value' => $this->value,
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

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?string
    {
        return $this->paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var int|float|null $value */
        return $this->matchValue($value);
    }
}
