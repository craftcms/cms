<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\enums\LicenseKeyStatus;
use craft\app\events\Event;
use craft\app\events\RegisterCpAlertsEvent;

/**
 * Class Cp
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Cp
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterCpAlertsEvent The event that is triggered when registering CP alerts.
     */
    const EVENT_REGISTER_ALERTS = 'registerAlerts';

    // Static
    // =========================================================================

    /**
     * @param string|null $path
     * @param boolean     $fetch
     *
     * @return array
     */
    public static function alerts($path = null, $fetch = false)
    {
        $alerts = [];
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return $alerts;
        }

        if (Craft::$app->getUpdates()->getIsUpdateInfoCached() || $fetch) {
            // Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
            // relying on cached Elliott info
            $updateModel = Craft::$app->getUpdates()->getUpdates();

            // Get the license key status
            $licenseKeyStatus = Craft::$app->getEt()->getLicenseKeyStatus();

            // Invalid license?
            if ($licenseKeyStatus == LicenseKeyStatus::Invalid) {
                $alerts[] = Craft::t('app', 'Your license key is invalid.');
            } else if (Craft::$app->getHasWrongEdition()) {
                $alerts[] = Craft::t('app', 'You’re running Craft {edition} with a Craft {licensedEdition} license.', [
                        'edition' => Craft::$app->getEditionName(),
                        'licensedEdition' => Craft::$app->getLicensedEditionName()
                    ]).
                    ' <a class="go edition-resolution">'.Craft::t('app', 'Resolve').'</a>';
            }

            if ($path != 'updates' && $user->can('performUpdates')) {
                if (!empty($updateModel->app->releases)) {
                    if (Craft::$app->getUpdates()->criticalCraftUpdateAvailable($updateModel->app->releases)) {
                        $alerts[] = Craft::t('app', 'There’s a critical Craft CMS update available.').
                            ' <a class="go nowrap" href="'.Url::url('updates').'">'.Craft::t('app', 'Go to Updates').'</a>';
                    }
                }
            }

            // Domain mismatch?
            if ($licenseKeyStatus == LicenseKeyStatus::Mismatched) {
                $licensedDomain = Craft::$app->getEt()->getLicensedDomain();
                $licenseKeyPath = Craft::$app->getPath()->getLicenseKeyPath();
                $licenseKeyFile = Io::getFolderName($licenseKeyPath,
                        false).'/'.Io::getFilename($licenseKeyPath);

                $message = Craft::t('app', 'The license located at {file} belongs to {domain}.',
                    [
                        'file' => $licenseKeyFile,
                        'domain' => '<a href="http://'.$licensedDomain.'" target="_blank">'.$licensedDomain.'</a>'
                    ]);

                // Can they actually do something about it?
                if ($user->admin) {
                    $action = '<a class="go domain-mismatch">'.Craft::t('app', 'Transfer it to this domain?').'</a>';
                } else {
                    $action = Craft::t('app', 'Please notify one of your site’s admins.');
                }

                $alerts[] = $message.' '.$action;
            }
        }

        // Give plugins a chance to add their own alerts
        $event = new RegisterCpAlertsEvent();
        Event::trigger(self::class, self::EVENT_REGISTER_ALERTS, $event);
        $alerts = array_merge($alerts, $event->alerts);

        return $alerts;
    }
}
