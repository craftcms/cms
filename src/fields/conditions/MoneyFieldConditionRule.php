<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseNumberConditionRule;
use craft\fields\Money;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\MoneyHelper;
use Money\Currency;
use Money\Money as MoneyLibrary;
use yii\base\InvalidConfigException;

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
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        // Hold setting of the value attribute until we have all the info we need
        if (isset($values['value']) && is_array($values['value'])) {
            /** @var array $value */
            $value = ArrayHelper::remove($values, 'value');
        }

        if (isset($values['maxValue']) && is_array($values['maxValue'])) {
            /** @var array $maxValue */
            $maxValue = ArrayHelper::remove($values, 'maxValue');
        }

        parent::setAttributes($values, $safeOnly);

        $field = $this->field();
        if (!$field instanceof Money) {
            throw new InvalidConfigException();
        }

        if (isset($value) && isset($this->_fieldUid)) {
            if (!isset($value['currency'])) {
                $value['currency'] = $field->currency;
            }
            $this->value = MoneyHelper::toDecimal(MoneyHelper::toMoney($value));
        }

        if (isset($maxValue) && isset($this->_fieldUid)) {
            if (!isset($maxValue['currency'])) {
                $maxValue['currency'] = $field->currency;
            }
            $this->maxValue = MoneyHelper::toDecimal(MoneyHelper::toMoney($maxValue));
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $field = $this->field();
        if (!$field instanceof Money) {
            throw new InvalidConfigException();
        }

        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if ($this->operator === self::OPERATOR_BETWEEN) {
            $maxValue = is_numeric($this->maxValue) ? MoneyHelper::toNumber(MoneyHelper::toMoney(['value' => $this->maxValue, 'currency' => $field->currency])) : $this->maxValue;

            return Html::tag('div',
                Html::hiddenLabel(Craft::t('app', 'Min Value'), 'min') .
                // Min value (value) input
                Cp::moneyInputHtml($this->inputOptions()) .
                Html::tag('span', Craft::t('app', 'and')) .
                Html::hiddenLabel(Craft::t('app', 'Max Value'), 'max') .
                // Max value input
                Cp::moneyInputHtml(array_merge(
                    $this->inputOptions(),
                    ['id' => 'maxValue', 'name' => 'maxValue', 'value' => $maxValue]
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

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?string
    {
        if (!$this->field() instanceof Money) {
            return null;
        }

        return $this->paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof Money) {
            return true;
        }

        if ($value instanceof MoneyLibrary) {
            $value = (float)$value->getAmount();
        }

        /** @var int|float|null $value */
        return $this->matchValue($value);
    }
}
