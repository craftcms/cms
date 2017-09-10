<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\enums\LicenseKeyStatus;
use craft\events\RegisterCpAlertsEvent;
use yii\base\Event;

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
     * @param bool        $fetch
     *
     * @return array
     */
    public static function alerts(string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return $alerts;
        }

        $updatesService = Craft::$app->getUpdates();

        if ($updatesService->getIsUpdateInfoCached() || $fetch) {
            // Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
            // relying on cached Elliott info
            $updatesService->getUpdates();

            // Get the license key status
            $licenseKeyStatus = Craft::$app->getEt()->getLicenseKeyStatus();

            // Invalid license?
            if ($licenseKeyStatus === LicenseKeyStatus::Invalid) {
                $alerts[] = Craft::t('app', 'Your license key is invalid.');
            } else if (Craft::$app->getHasWrongEdition()) {
                $alerts[] = Craft::t('app', 'You’re running Craft {edition} with a Craft {licensedEdition} license.', [
                        'edition' => Craft::$app->getEditionName(),
                        'licensedEdition' => Craft::$app->getLicensedEditionName()
                    ]).
                    ' <a class="go edition-resolution">'.Craft::t('app', 'Resolve').'</a>';
            }

            if (
                $path !== 'utilities/updates' &&
                $user->can('utility:updates') &&
                $updatesService->getIsCriticalUpdateAvailable()
            ) {
                $alerts[] = Craft::t('app', 'A critical update is available.').
                    ' <a class="go nowrap" href="'.UrlHelper::url('utilities/updates').'">'.Craft::t('app', 'Go to Updates').'</a>';
            }

            // Domain mismatch?
            if ($licenseKeyStatus === LicenseKeyStatus::Mismatched) {
                $licensedDomain = Craft::$app->getEt()->getLicensedDomain();

                $message = Craft::t('app', 'The license located at {file} belongs to {domain}.',
                    [
                        'file' => 'config/license.key',
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
