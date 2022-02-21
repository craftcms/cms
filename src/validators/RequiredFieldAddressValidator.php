<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use Craft;
use craft\elements\Address;
use yii\base\Exception;
use yii\validators\Validator;

/**
 * Class AddressValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RequiredFieldAddressValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public $skipOnEmpty = false;

    /**
     * @var AddressFormat[] The memoized array of formatters by country code
     */
    private array $_countryFormatters = [];

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function validateAttribute($model, $attribute): void
    {
        if (!$model instanceof Address) {
            throw new Exception('AddressValidator can only be used with Address elements.');
        }

        // Country code is already required for addresses
        $countryCode = $model->getCountryCode();

        // Cache the country formatter
        if (!isset($this->_countryFormatters[$countryCode])) {
            $this->_countryFormatters[$countryCode] = Craft::$app->getAddresses()->getAddressFormatRepository()->get($countryCode);
        }

        if (in_array($attribute, $this->_countryFormatters[$countryCode]->getRequiredFields(), false) && !$model->$attribute) {
            $message = Craft::t('app', '{attribute} is required.');
            $this->addError($model, $attribute, $message);
        }
    }
}
