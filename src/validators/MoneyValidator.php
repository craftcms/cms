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
     * @var array
     */
    public array $allowedCurrencies = [];

    /**
     * @var string
     */
    public string $notAllowedMessage;

    /**
     * @var string
     */
    public string $invalidMinorUnitMessage;

    /**
     * @var ISOCurrencies
     */
    private ISOCurrencies $_allIsoCurrencies;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->notAllowedMessage)) {
            $this->notAllowedMessage = Craft::t('app', 'The currency code “{currencyCode}” is not allowed.');
        }

        if (!isset($this->invalidMinorUnitMessage)) {
            $this->invalidMinorUnitMessage = Craft::t('app', 'The currency code “{currencyCode}” is not allowed.');
        }

        if (!isset($this->_allIsoCurrencies)) {
            $this->_allIsoCurrencies = new ISOCurrencies();
        }
    }

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

        if (!empty($this->allowedCurrencies) && !in_array($value->getCurrency()->getCode(), $this->allowedCurrencies, true)) {
            $this->addError($model, $attribute, $this->notAllowedMessage, [
                'currencyCode' => $value->getCurrency()->getCode()
            ]);
        }

        if ($normalized) {
            // Update the value on the model to the DateTime object
            $model->$attribute = $value;
        }
    }
}