<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\et\EtTransport;
use craft\helpers\App;
use craft\helpers\Io;
use craft\helpers\Json;
use craft\models\AppNewRelease;
use craft\models\AppUpdate;
use craft\models\Et as EtModel;
use craft\models\PluginNewRelease;
use craft\models\PluginUpdate;
use craft\models\Update;
use craft\models\UpgradeInfo;
use craft\models\UpgradePurchase;
use GuzzleHttp\Client;
use yii\base\Component;

/**
 * Class Et service.
 *
 * An instance of the Et service is globally accessible in Craft via [[Application::et `Craft::$app->getEt()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Et extends Component
{
    // Constants
    // =========================================================================

    const ENDPOINT_PING = 'app/ping';
    const ENDPOINT_CHECK_FOR_UPDATES = 'app/checkForUpdates';
    const ENDPOINT_TRANSFER_LICENSE = 'app/transferLicenseToCurrentDomain';
    const ENDPOINT_GET_UPGRADE_INFO = 'app/getUpgradeInfo';
    const ENDPOINT_GET_COUPON_PRICE = 'app/getCouponPrice';
    const ENDPOINT_PURCHASE_UPGRADE = 'app/purchaseUpgrade';
    const ENDPOINT_GET_UPDATE_FILE_INFO = 'app/getUpdateFileInfo';
    const ENDPOINT_REGISTER_PLUGIN = 'plugins/registerPlugin';
    const ENDPOINT_UNREGISTER_PLUGIN = 'plugins/unregisterPlugin';
    const ENDPOINT_TRANSFER_PLUGIN = 'plugins/transferPlugin';

    // Properties
    // =========================================================================

    /**
     * @var string The host name to send Elliott requests to.
     */
    public $elliottBaseUrl = 'https://elliott.craftcms.com';

    /**
     * @var string Query string to append to Elliott request URLs.
     */
    public $elliottQuery;

    /**
     * @var string The host name to send download requests to.
     */
    public $downloadBaseUrl = 'https://download.craftcdn.com';

    // Public Methods
    // =========================================================================

    /**
     * @return EtModel|null
     */
    public function ping()
    {
        $et = $this->_createEtTransport(static::ENDPOINT_PING);
        $etResponse = $et->phoneHome();

        return $etResponse;
    }

    /**
     * Checks if any new updates are available.
     *
     * @param $updateInfo
     *
     * @return EtModel|null
     */
    public function checkForUpdates($updateInfo)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_CHECK_FOR_UPDATES);
        $et->setData($updateInfo);
        $etResponse = $et->phoneHome();

        if ($etResponse) {
            // Populate the base Update model
            $updateModel = new Update();
            $updateModel->setAttributes($etResponse->data, false);

            // Populate any Craft specific attributes.
            $appUpdateModel = new AppUpdate();
            $appUpdateModel->setAttributes($etResponse->data['app'], false);
            $updateModel->app = $appUpdateModel;

            // Populate any new Craft release information.
            $appUpdateModel->releases = [];

            foreach ($etResponse->data['app']['releases'] as $key => $appReleaseInfo) {
                /** @var array $appReleaseInfo */
                $appReleaseModel = new AppNewRelease();
                $appReleaseModel->setAttributes($appReleaseInfo, false);

                $appUpdateModel->releases[$key] = $appReleaseModel;
            }

            // For every plugin, populate their base information.
            $updateModel->plugins = [];

            foreach ($etResponse->data['plugins'] as $pluginHandle => $pluginUpdateInfo) {
                /** @var array $pluginUpdateInfo */
                $pluginUpdateModel = new PluginUpdate();
                $pluginUpdateModel->setAttributes($pluginUpdateInfo, false);

                // Now populate a plugin’s release information.
                $pluginUpdateModel->releases = [];

                foreach ($pluginUpdateInfo['releases'] as $key => $pluginReleaseInfo) {
                    /** @var array $pluginReleaseInfo */
                    $pluginReleaseModel = new PluginNewRelease();
                    $pluginReleaseModel->setAttributes($pluginReleaseInfo, false);

                    $pluginUpdateModel->releases[$key] = $pluginReleaseModel;
                }

                $updateModel->plugins[$pluginHandle] = $pluginUpdateModel;
            }

            // Put it all back on Et.
            $etResponse->data = $updateModel;

            return $etResponse;
        }

        return null;
    }

    /**
     * @param string $handle
     *
     * @return string|null The update's md5
     */
    public function getUpdateFileInfo($handle)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_GET_UPDATE_FILE_INFO);

        if ($handle !== 'craft') {
            $et->setHandle($handle);
            /** @var Plugin $plugin */
            $plugin = Craft::$app->getPlugins()->getPlugin($handle);

            if ($plugin) {
                $pluginUpdateModel = new PluginUpdate();
                $pluginUpdateModel->class = $plugin->getHandle();
                $pluginUpdateModel->localVersion = $plugin->version;

                $et->setData($pluginUpdateModel);
            }
        }

        $etResponse = $et->phoneHome();

        if ($etResponse) {
            return $etResponse->data;
        }

        return null;
    }

    /**
     * @param string $downloadPath
     * @param string $md5
     * @param string $handle
     *
     * @return string|false The name of the update file, or false if a problem occurred
     */
    public function downloadUpdate($downloadPath, $md5, $handle)
    {
        if (Io::folderExists($downloadPath)) {
            $downloadPath .= '/'.$md5.'.zip';
        }

        $updateModel = Craft::$app->getUpdates()->getUpdates();

        if ($handle == 'craft') {
            $localVersion = $updateModel->app->localVersion;
            $targetVersion = $updateModel->app->latestVersion;
            $uriPrefix = 'craft';
        } else {
            // Find the plugin whose class matches the handle
            $localVersion = null;
            $targetVersion = null;
            $uriPrefix = 'plugins/'.$handle;

            foreach ($updateModel->plugins as $plugin) {
                if (strtolower($plugin->class) == $handle) {
                    $localVersion = $plugin->localVersion;
                    $targetVersion = $plugin->latestVersion;
                    break;
                }
            }

            if ($localVersion === null) {
                Craft::warning('Couldn’t find the plugin "'.$handle.'" in the update model.');

                return false;
            }
        }

        $xy = App::majorMinorVersion($targetVersion);
        $url = "{$this->downloadBaseUrl}/{$uriPrefix}/{$xy}/{$targetVersion}/Patch/{$localVersion}/{$md5}.zip";

        $client = new Client([
            'timeout' => 240,
            'connect_timeout' => 30,
        ]);

        // Potentially long-running request, so close session to prevent session blocking on subsequent requests.
        Craft::$app->getSession()->close();

        $response = $client->request('get', $url);

        if ($response->getStatusCode() != 200) {
            Craft::warning('Error in downloading '.$url.' Response: '.$response->getBody());

            return false;
        }

        $body = $response->getBody();

        // Make sure we're at the beginning of the stream.
        $body->rewind();

        // Write it out to the file
        Io::writeToFile($downloadPath, $body, true);

        // Close the stream.
        $body->close();

        return Io::getFilename($downloadPath);
    }

    /**
     * Transfers the installed license to the current domain.
     *
     * @return true|string Returns true if the request was successful, otherwise returns the error.
     */
    public function transferLicenseToCurrentDomain()
    {
        $et = $this->_createEtTransport(static::ENDPOINT_TRANSFER_LICENSE);
        $etResponse = $et->phoneHome();

        if (!empty($etResponse->data['success'])) {
            return true;
        }

        // Did they at least say why?
        if (!empty($etResponse->errors)) {
            switch ($etResponse->errors[0]) {
                // Validation errors
                case 'not_public_domain': {
                    // So...
                    return true;
                }

                default: {
                    $error = $etResponse->data['error'];
                }
            }
        } else {
            $error = Craft::t('app', 'Craft is unable to transfer your license to this domain at this time.');
        }

        return $error;
    }

    /**
     * Fetches info about the available Craft editions from Elliott.
     *
     * @return EtModel|null
     */
    public function fetchUpgradeInfo()
    {
        $et = $this->_createEtTransport(static::ENDPOINT_GET_UPGRADE_INFO);
        $etResponse = $et->phoneHome();

        if ($etResponse) {
            $etResponse->data = new UpgradeInfo($etResponse->data);
        }

        return $etResponse;
    }

    /**
     * Fetches the price of an upgrade with a coupon applied to it.
     *
     * @param integer $edition
     * @param string  $couponCode
     *
     * @return EtModel|null
     */
    public function fetchCouponPrice($edition, $couponCode)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_GET_COUPON_PRICE);
        $et->setData(['edition' => $edition, 'couponCode' => $couponCode]);
        $etResponse = $et->phoneHome();

        return $etResponse;
    }

    /**
     * Attempts to purchase an edition upgrade.
     *
     * @param UpgradePurchase $model
     *
     * @return boolean
     */
    public function purchaseUpgrade(UpgradePurchase $model)
    {
        if ($model->validate()) {
            $et = $this->_createEtTransport(static::ENDPOINT_PURCHASE_UPGRADE);
            $et->setData($model);
            $etResponse = $et->phoneHome();

            if (!empty($etResponse->data['success'])) {
                // Success! Let's get this sucker installed.
                Craft::$app->setEdition($model->edition);

                return true;
            }

            // Did they at least say why?
            if (!empty($etResponse->errors)) {
                switch ($etResponse->errors[0]) {
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
                        $error = $etResponse->errors[0];
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
     * Registers a given plugin with the current Craft license.
     *
     * @param string $pluginHandle The plugin handle that should be registered
     *
     * @return EtModel
     */
    public function registerPlugin($pluginHandle)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_REGISTER_PLUGIN);
        $et->setData([
            'pluginHandle' => $pluginHandle
        ]);
        $etResponse = $et->phoneHome();

        return $etResponse;
    }

    /**
     * Transfers a given plugin to the current Craft license.
     *
     * @param string $pluginHandle The plugin handle that should be transferred
     *
     * @return EtModel
     */
    public function transferPlugin($pluginHandle)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_TRANSFER_PLUGIN);
        $et->setData([
            'pluginHandle' => $pluginHandle
        ]);
        $etResponse = $et->phoneHome();

        return $etResponse;
    }

    /**
     * Unregisters a given plugin from the current Craft license.
     *
     * @param string $pluginHandle The plugin handle that should be unregistered
     *
     * @return EtModel
     */
    public function unregisterPlugin($pluginHandle)
    {
        $et = $this->_createEtTransport(static::ENDPOINT_UNREGISTER_PLUGIN);
        $et->setData([
            'pluginHandle' => $pluginHandle
        ]);
        $etResponse = $et->phoneHome();

        if (!empty($etResponse->data['success'])) {
            // Remove our record of the license key
            Craft::$app->getPlugins()->setPluginLicenseKey($pluginHandle, null);
        }

        return $etResponse;
    }

    /**
     * Returns the license key status, or false if it's unknown.
     *
     * @return string|false
     */
    public function getLicenseKeyStatus()
    {
        return Craft::$app->getCache()->get('licenseKeyStatus');
    }

    /**
     * Returns the domain that the installed license key is licensed for, null if it's not set yet, or false if it's
     * unknown.
     *
     * @return string|null|false
     */
    public function getLicensedDomain()
    {
        return Craft::$app->getCache()->get('licensedDomain');
    }

    /**
     * Creates a new EtModel with provided JSON, and returns it if it's valid.
     *
     * @param array $attributes
     *
     * @return EtModel|null
     */
    public function decodeEtModel($attributes)
    {
        if ($attributes) {
            $attributes = Json::decode($attributes);

            if (is_array($attributes)) {
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
     *
     * @return EtTransport
     */
    private function _createEtTransport($endpoint)
    {
        $url = $this->elliottBaseUrl.'/actions/elliott/'.$endpoint;

        if ($this->elliottQuery) {
            $url .= '?'.$this->elliottQuery;
        }

        return new EtTransport($url);
    }
}
