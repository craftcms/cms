<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Plugin;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\models\AppNewRelease;
use craft\app\models\AppUpdate;
use craft\app\models\Et as EtModel;
use craft\app\models\PluginNewRelease;
use craft\app\models\PluginUpdate;
use craft\app\models\Update;
use craft\app\models\UpgradeInfo;
use craft\app\models\UpgradePurchase;
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

    const Ping = 'https://elliott.craftcms.com/actions/elliott/app/ping';
    const CheckForUpdates = 'https://elliott.craftcms.com/actions/elliott/app/checkForUpdates';
    const TransferLicense = 'https://elliott.craftcms.com/actions/elliott/app/transferLicenseToCurrentDomain';
    const GetUpgradeInfo = 'https://elliott.craftcms.com/actions/elliott/app/getUpgradeInfo';
    const GetCouponPrice = 'https://elliott.craftcms.com/actions/elliott/app/getCouponPrice';
    const PurchaseUpgrade = 'https://elliott.craftcms.com/actions/elliott/app/purchaseUpgrade';
    const GetUpdateFileInfo = 'https://elliott.craftcms.com/actions/elliott/app/getUpdateFileInfo';
    const RegisterPlugin = 'https://elliott.craftcms.com/actions/elliott/plugins/registerPlugin';
    const UnregisterPlugin = 'https://elliott.craftcms.com/actions/elliott/plugins/unregisterPlugin';
    const TransferPlugin = 'https://elliott.craftcms.com/actions/elliott/plugins/transferPlugin';

    // Public Methods
    // =========================================================================

    /**
     * @return EtModel|null
     */
    public function ping()
    {
        $et = new \craft\app\et\Et(static::Ping);
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
        $et = new \craft\app\et\Et(static::CheckForUpdates);
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
        $et = new \craft\app\et\Et(static::GetUpdateFileInfo);

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
        $buildVersion = $updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild;

        if ($handle == 'craft') {
            $path = 'https://download.craftcdn.com/craft/'.$updateModel->app->latestVersion.'/'.$buildVersion.'/Patch/'.($handle == 'craft' ? $updateModel->app->localBuild : $updateModel->app->localVersion.'.'.$updateModel->app->localBuild).'/'.$md5.'.zip';
        } else {
            $localVersion = null;
            $localBuild = null;
            $latestVersion = null;
            $latestBuild = null;

            foreach ($updateModel->plugins as $plugin) {
                if (strtolower($plugin->class) == $handle) {
                    $parts = explode('.', $plugin->localVersion);
                    $localVersion = $parts[0].'.'.$parts[1];
                    $localBuild = $parts[2];

                    $parts = explode('.', $plugin->latestVersion);
                    $latestVersion = $parts[0].'.'.$parts[1];
                    $latestBuild = $parts[2];

                    break;
                }
            }

            $path = 'https://download.craftcdn.com/plugins/'.$handle.'/'.$latestVersion.'/'.$latestVersion.'.'.$latestBuild.'/Patch/'.$localVersion.'.'.$localBuild.'/'.$md5.'.zip';
        }

        $et = new \craft\app\et\Et($path, 240);
        $et->setDestinationFilename($downloadPath);

        if (($filename = $et->phoneHome()) !== null) {
            return $filename;
        }

        return false;
    }

    /**
     * Transfers the installed license to the current domain.
     *
     * @return true|string Returns true if the request was successful, otherwise returns the error.
     */
    public function transferLicenseToCurrentDomain()
    {
        $et = new \craft\app\et\Et(static::TransferLicense);
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
        $et = new \craft\app\et\Et(static::GetUpgradeInfo);
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
        $et = new \craft\app\et\Et(static::GetCouponPrice);
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
            $et = new \craft\app\et\Et(static::PurchaseUpgrade);
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
        $et = new \craft\app\et\Et(static::RegisterPlugin);
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
        $et = new \craft\app\et\Et(static::TransferPlugin);
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
        $et = new \craft\app\et\Et(static::UnregisterPlugin);
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

                // Make sure it's valid. (At a minimum, localBuild and localVersion
                // should be set.)
                if ($etModel->validate()) {
                    return $etModel;
                }
            }
        }

        return null;
    }
}
