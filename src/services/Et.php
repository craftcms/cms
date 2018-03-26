<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\et\EtTransport;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\Et as EtModel;
use craft\models\UpgradeInfo;
use craft\models\UpgradePurchase;
use yii\base\Component;

/**
 * ET service.
 * An instance of the ET service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getEt()|<code>Craft::$app->et</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Et extends Component
{
    // Constants
    // =========================================================================

    const ENDPOINT_GET_UPGRADE_INFO = 'app/getUpgradeInfo';
    const ENDPOINT_GET_COUPON_PRICE = 'app/getCouponPrice';
    const ENDPOINT_PURCHASE_UPGRADE = 'app/purchaseUpgrade';

    // Properties
    // =========================================================================

    /**
     * @var string The host name to send Elliott requests to.
     */
    public $elliottBaseUrl = 'https://elliott.craftcms.com';

    /**
     * @var string|null Query string to append to Elliott request URLs.
     */
    public $elliottQuery;

    // Public Methods
    // =========================================================================

    /**
     * Fetches info about the available Craft editions from Elliott.
     *
     * @return EtModel|null
     */
    public function fetchUpgradeInfo()
    {
        $et = $this->_createEtTransport(self::ENDPOINT_GET_UPGRADE_INFO);
        $etResponse = $et->phoneHome();

        if ($etResponse) {
            $etResponse->data = new UpgradeInfo($etResponse->data);
        }

        return $etResponse;
    }

    /**
     * Fetches the price of an upgrade with a coupon applied to it.
     *
     * @param int $edition
     * @param string $couponCode
     * @return EtModel|null
     */
    public function fetchCouponPrice(int $edition, string $couponCode)
    {
        $et = $this->_createEtTransport(self::ENDPOINT_GET_COUPON_PRICE);
        $et->setData(['edition' => $edition, 'couponCode' => $couponCode]);

        return $et->phoneHome();
    }

    /**
     * Attempts to purchase an edition upgrade.
     *
     * @param UpgradePurchase $model
     * @return bool
     */
    public function purchaseUpgrade(UpgradePurchase $model): bool
    {
        if ($model->validate()) {
            $et = $this->_createEtTransport(self::ENDPOINT_PURCHASE_UPGRADE);
            $et->setData($model);
            $etResponse = $et->phoneHome();

            if (!empty($etResponse->data['success'])) {
                // Success! Let's get this sucker installed.
                Craft::$app->setEdition($model->edition);

                return true;
            }

            // Did they at least say why?
            if (!empty($etResponse->responseErrors)) {
                switch ($etResponse->responseErrors[0]) {
                    // Validation errors
                    case 'edition_doesnt_exist':
                        $error = Craft::t('app', 'The selected edition doesn’t exist anymore.');
                        break;
                    case 'invalid_license_key':
                        $error = Craft::t('app', 'Your license key is invalid.');
                        break;
                    case 'license_has_edition':
                        $error = Craft::t('app', 'Your Craft license already has this edition.');
                        break;
                    case 'price_mismatch':
                        $error = Craft::t('app', 'The cost of this edition just changed.');
                        break;
                    case 'unknown_error':
                        $error = Craft::t('app', 'An unknown error occurred.');
                        break;
                    case 'invalid_coupon_code':
                        $error = Craft::t('app', 'Invalid coupon code.');
                        break;

                    // Stripe errors
                    case 'incorrect_number':
                        $error = Craft::t('app', 'The card number is incorrect.');
                        break;
                    case 'invalid_number':
                        $error = Craft::t('app', 'The card number is invalid.');
                        break;
                    case 'invalid_expiry_month':
                        $error = Craft::t('app', 'The expiration month is invalid.');
                        break;
                    case 'invalid_expiry_year':
                        $error = Craft::t('app', 'The expiration year is invalid.');
                        break;
                    case 'invalid_cvc':
                        $error = Craft::t('app', 'The security code is invalid.');
                        break;
                    case 'incorrect_cvc':
                        $error = Craft::t('app', 'The security code is incorrect.');
                        break;
                    case 'expired_card':
                        $error = Craft::t('app', 'Your card has expired.');
                        break;
                    case 'card_declined':
                        $error = Craft::t('app', 'Your card was declined.');
                        break;
                    case 'processing_error':
                        $error = Craft::t('app', 'An error occurred while processing your card.');
                        break;

                    default:
                        $error = $etResponse->responseErrors[0];
                }
            } else {
                // Something terrible must have happened!
                $error = Craft::t('app', 'Craft is unable to purchase an edition upgrade at this time.');
            }

            $model->addError('response', $error);
        }

        return false;
    }

    /**
     * Creates a new EtModel with provided JSON, and returns it if it's valid.
     *
     * @param string $attributes
     * @return EtModel|null
     */
    public function decodeEtModel(string $attributes)
    {
        if ($attributes) {
            $attributes = Json::decode($attributes);

            if (is_array($attributes)) {
                ArrayHelper::rename($attributes, 'errors', 'responseErrors');
                $etModel = new EtModel($attributes);

                // Make sure it's valid.
                if ($etModel->validate()) {
                    return $etModel;
                }
            }
        }

        return null;
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a new ET Transport object for the given endpoint.
     *
     * @param string $endpoint
     * @return EtTransport
     */
    private function _createEtTransport(string $endpoint): EtTransport
    {
        $url = $this->elliottBaseUrl.'/actions/elliott/'.$endpoint;

        if ($this->elliottQuery) {
            $url .= '?'.$this->elliottQuery;
        }

        return new EtTransport($url);
    }
}
