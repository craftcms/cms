<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use yii\validators\Validator;

/**
 * Class MoneyValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MoneyValidator extends Validator
{
    /**
     * @var int|float|null
     */
    public int|null|float $max = null;

    /**
     * @var int|float|null
     */
    public int|null|float $min = null;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new NumberFormatter(Craft::$app->getFormattingLocale()->id, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        $value = $model->$attribute;

        if ($normalized = (!$value instanceof Money)) {
            $currency = !$value['currency'] instanceof Currency ? new Currency($value['currency']) : $value['currency'];
            $value = new Money($value['value'], $currency);
        }

        if ($this->max !== null) {
            $max = new Money($this->max, $value->getCurrency());

            if ($value->greaterThan($max)) {
                $this->addError($model, $attribute, Craft::t('app', '{attribute} must be no greater than {max}.', [
                    'attribute' => $model->getAttributeLabel($attribute),
                    'max' => $moneyFormatter->format($max),
                ]));
            }
        }

        if ($this->min !== null) {
            $min = new Money($this->min, $value->getCurrency());

            if ($value->lessThan($min)) {
                $this->addError($model, $attribute, Craft::t('app', '{attribute} must be no less than {min}.', [
                    'attribute' => $model->getAttributeLabel($attribute),
                    'min' => $moneyFormatter->format($min),
                ]));
            }
        }

        if ($normalized) {
            // Update the value on the model to the Money object
            $model->$attribute = $value;
        }
    }
}
