<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\elements\Address;
use craft\helpers\StringHelper;
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
     * @var string[] The memoized array of formatters by country code
     */
    private $_countryFormatter = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->skipOnEmpty = false;
    }

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
        if(!isset($this->_countryFormatter[$countryCode])) {
            $this->_countryFormatter[$countryCode] = Craft::$app->getAddresses()->getAddressFormatRepository()->get($countryCode);
        }

        if (in_array($attribute, $this->_countryFormatter[$countryCode]->getRequiredFields(), false) && !$model->$attribute) {
            $message = Craft::t('app', '{attribute} is required.', ['attribute' => StringHelper::toTitleCase($attribute)]);
            $this->addError($model, $attribute, $message);
        }
    }
}
