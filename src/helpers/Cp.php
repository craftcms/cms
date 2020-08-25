<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
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
 * @since 3.0.0
 */
class Cp
{
    /**
     * @event RegisterCpAlertsEvent The event that is triggered when registering control panel alerts.
     */
    const EVENT_REGISTER_ALERTS = 'registerAlerts';

    /**
     * @param string|null $path
     * @param bool $fetch
     * @return array
     */
    public static function alerts(string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $user = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$user) {
            return $alerts;
        }

        $updatesService = Craft::$app->getUpdates();

        if ($updatesService->getIsUpdateInfoCached() || $fetch) {
            // Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
            // relying on cached Craftnet info
            $updatesService->getUpdates();

            // Get the license key status
            $licenseKeyStatus = Craft::$app->getCache()->get('licenseKeyStatus');

            if ($path !== 'plugin-store/upgrade-craft') {
                // Invalid license?
                if ($licenseKeyStatus === LicenseKeyStatus::Invalid) {
                    $alerts[] = Craft::t('app', 'Your Craft license key is invalid.');
                } else if (Craft::$app->getHasWrongEdition()) {
                    $message = Craft::t('app', 'You’re running Craft {edition} with a Craft {licensedEdition} license.', [
                            'edition' => Craft::$app->getEditionName(),
                            'licensedEdition' => Craft::$app->getLicensedEditionName()
                        ]) . ' ';
                    if ($user->admin) {
                        if ($generalConfig->allowAdminChanges) {
                            $message .= '<a class="go" href="' . UrlHelper::url('plugin-store/upgrade-craft') . '">' . Craft::t('app', 'Resolve') . '</a>';
                        } else {
                            $message .= Craft::t('app', 'Please fix on an environment where administrative changes are allowed.');
                        }
                    } else {
                        $message .= Craft::t('app', 'Please notify one of your site’s admins.');
                    }

                    $alerts[] = $message;
                }
            }

            if (
                $path !== 'utilities/updates' &&
                $user->can('utility:updates') &&
                $updatesService->getIsCriticalUpdateAvailable()
            ) {
                $alerts[] = Craft::t('app', 'A critical update is available.') .
                    ' <a class="go nowrap" href="' . UrlHelper::url('utilities/updates') . '">' . Craft::t('app', 'Go to Updates') . '</a>';
            }

            // Domain mismatch?
            if ($licenseKeyStatus === LicenseKeyStatus::Mismatched) {
                $licensedDomain = Craft::$app->getCache()->get('licensedDomain');
                $domainLink = '<a href="http://' . $licensedDomain . '" rel="noopener" target="_blank">' . $licensedDomain . '</a>';

                if (defined('CRAFT_LICENSE_KEY')) {
                    $message = Craft::t('app', 'The license key in use belongs to {domain}', [
                        'domain' => $domainLink
                    ]);
                } else {
                    $keyPath = Craft::$app->getPath()->getLicenseKeyPath();

                    // If the license key path starts with the root project path, trim the project path off
                    $rootPath = Craft::getAlias('@root');
                    if (strpos($keyPath, $rootPath . '/') === 0) {
                        $keyPath = substr($keyPath, strlen($rootPath) + 1);
                    }

                    $message = Craft::t('app', 'The license located at {file} belongs to {domain}.', [
                        'file' => $keyPath,
                        'domain' => $domainLink
                    ]);
                }

                $alerts[] = $message . ' <a class="go" href="https://craftcms.com/support/resolving-mismatched-licenses">' . Craft::t('app', 'Learn more') . '</a>';
            }

            // Any plugin issues?
            if ($path != 'settings/plugins') {
                $pluginsService = Craft::$app->getPlugins();
                $issuePlugins = [];
                foreach ($pluginsService->getAllPlugins() as $pluginHandle => $plugin) {
                    if ($pluginsService->hasIssues($pluginHandle)) {
                        $issuePlugins[] = $plugin->name;
                    }
                }
                if (!empty($issuePlugins)) {
                    if (count($issuePlugins) === 1) {
                        $message = Craft::t('app', 'There’s a licensing issue with the {name} plugin.', [
                            'name' => reset($issuePlugins),
                        ]);
                    } else {
                        $message = Craft::t('app', '{num} plugins have licensing issues.', [
                            'num' => count($issuePlugins),
                        ]);
                    }
                    $message .= ' ';
                    if ($user->admin) {
                        if ($generalConfig->allowAdminChanges) {
                            $message .= '<a class="go" href="' . UrlHelper::cpUrl('settings/plugins') . '">' . Craft::t('app', 'Resolve') . '</a>';
                        } else {
                            $message .= Craft::t('app', 'Please fix on an environment where administrative changes are allowed.');
                        }
                    } else {
                        $message .= Craft::t('app', 'Please notify one of your site’s admins.');
                    }

                    $alerts[] = $message;
                }
            }
        }

        // Display an alert if there are pending project config YAML changes
        $projectConfig = Craft::$app->getProjectConfig();
        if (
            $path !== 'utilities/project-config' &&
            $user->can('utility:project-config') &&
            $projectConfig->areChangesPending()
        ) {
            $alerts[] = Craft::t('app', 'Your project config YAML files contain pending changes.') .
                ' ' . '<a class="go" href="' . UrlHelper::url('utilities/project-config') . '">' . Craft::t('app', 'Review') . '</a>';
        }

        // Display a warning if admin changes are allowed, and project.yaml is being used but not writable
        if (
            $user->admin &&
            $generalConfig->allowAdminChanges &&
            $projectConfig->getHadFileWriteIssues()
        ) {
            $alerts[] = Craft::t('app', 'Your {folder} folder isn’t writable.', [
                'folder' => "config/$projectConfig->folderName/",
            ]);
        }

        // Give plugins a chance to add their own alerts
        $event = new RegisterCpAlertsEvent();
        Event::trigger(self::class, self::EVENT_REGISTER_ALERTS, $event);
        $alerts = array_merge($alerts, $event->alerts);

        return $alerts;
    }
}
