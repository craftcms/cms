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
use Money\Money;
use Money\Parser\DecimalMoneyParser;
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
    public $max;

    /**
     * @var int|float|null
     */
    public $min;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;
        if ($normalized = (!$value instanceof Money)) {
            $currency = !$value['currency'] instanceof Currency ? new Currency($value['currency']) : $value['currency'];
            $value = new Money($value['money'], $currency);
        }

        if ($this->max !== null) {
            $max = (new DecimalMoneyParser(new ISOCurrencies()))
                ->parse((string)$this->max, $value->getCurrency());

            if ($value->greaterThan($max)) {
                $this->addError($model, $attribute, Craft::t('app', '{attribute} must be no greater than {max}.', [
                    'attribute' => $model->getAttributeLabel($attribute),
                    'max' => $this->max,
                ]));
            }
        }

        if ($this->min !== null) {
            $min = (new DecimalMoneyParser(new ISOCurrencies()))
                ->parse((string)$this->min, $value->getCurrency());

            if ($value->lessThan($min)) {
                $this->addError($model, $attribute, Craft::t('app', '{attribute} must be no less than {min}.', [
                    'attribute' => $model->getAttributeLabel($attribute),
                    'min' => $this->min,
                ]));
            }
        }

        if ($normalized) {
            // Update the value on the model to the DateTime object
            $model->$attribute = $value;
        }
    }
}