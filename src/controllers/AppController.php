<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\base\UtilityInterface;
use craft\enums\LicenseKeyStatus;
use craft\errors\MigrationException;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Update;
use craft\web\Controller;
use craft\web\ServiceUnavailableHttpException;
use Http\Client\Common\Exception\ServerErrorException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $allowAnonymous = [
        'migrate'
    ];

    // Public Methods
    // =========================================================================

    public function beforeAction($action)
    {
        if ($action->id === 'migrate') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Returns update info.
     *
     * @return Response
     * @throws BadRequestHttpException if the request doesn't accept a JSON response
     * @throws ForbiddenHttpException if the user doesn't have permission to perform updates or use the Updates utility
     */
    public function actionCheckForUpdates(): Response
    {
        $this->requireAcceptsJson();

        // Require either the 'performUpdates' or 'utility:updates' permission
        $user = Craft::$app->getUser();
        if (!$user->checkPermission('performUpdates') && !$user->checkPermission('utility:updates')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $request = Craft::$app->getRequest();
        $forceRefresh = (bool)$request->getParam('forceRefresh');
        $includeDetails = (bool)$request->getParam('includeDetails');

        $updates = Craft::$app->getUpdates()->getUpdates($forceRefresh);

        $allowUpdates = (
            Craft::$app->getConfig()->getGeneral()->allowUpdates &&
            Craft::$app->getUser()->checkPermission('performUpdates')
        );

        $res = [
            'total' => $updates->getTotal(),
            'critical' => $updates->getHasCritical(),
            'allowUpdates' => $allowUpdates,
        ];

        if ($includeDetails) {
            $res['updates'] = [
                'cms' => $this->_transformUpdate($allowUpdates, $updates->cms, 'craft', 'Craft CMS'),
                'plugins' => [],
            ];

            $pluginsService = Craft::$app->getPlugins();
            foreach ($updates->plugins as $handle => $update) {
                if (($plugin = $pluginsService->getPlugin($handle)) !== null) {
                    /** @var Plugin $plugin */
                    $res['updates']['plugins'][] = $this->_transformUpdate($allowUpdates, $update, $handle, $plugin->name);
                }
            }
        }

        return $this->asJson($res);
    }

    /**
     * Creates a DB backup (if configured to do so) and runs any pending Craft, plugin, & content migrations in one go.
     * This action can be used as a post-deploy webhook with site deployment services (like [DeployBot](https://deploybot.com/))
     * to minimize site downtime after a deployment.
     *
     * @throws ServerErrorException if something went wrong
     */
    public function actionMigrate()
    {
        $this->requirePostRequest();

        $updatesService = Craft::$app->getUpdates();
        $db = Craft::$app->getDb();

        // Get the handles in need of an update
        $handles = $updatesService->getPendingMigrationHandles(true);

        if (empty($handles)) {
            // That was easy
            return Craft::$app->getResponse();
        }

        // Bail if Craft is already in maintenance mode
        if (Craft::$app->getIsInMaintenanceMode()) {
            throw new ServiceUnavailableHttpException('Craft is already being updated.');
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        // Backup the DB?
        $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
        if ($backup) {
            try {
                $backupPath = $db->backup();
            } catch (\Throwable $e) {
                Craft::$app->disableMaintenanceMode();
                throw new ServerErrorHttpException('Error backing up the database.', 0, $e);
            }
        }

        // Run the migrations
        try {
            $updatesService->runMigrations($handles);
        } catch (MigrationException $e) {
            // Do we have a backup?
            $restored = false;
            if (!empty($backupPath)) {
                // Attempt a restore
                try {
                    $db->restore($backupPath);
                    $restored = true;
                } catch (\Throwable $restoreException) {
                    // Just log it
                    Craft::$app->getErrorHandler()->logException($restoreException);
                }
            }

            $error = 'An error occurred running nuw migrations.';
            if ($restored) {
                $error .= ' The database has been restored to its previous state.';
            } else if (isset($restoreException)) {
                $error .= ' The database could not be restored due to a separate error: '.$restoreException->getMessage();
            } else {
                $error .= ' The database has not been restored.';
            }

            Craft::$app->disableMaintenanceMode();
            throw new ServerErrorHttpException($error, 0, $e);
        }

        Craft::$app->disableMaintenanceMode();
        return Craft::$app->getResponse();
    }

    /**
     * Returns the badge count for the Utilities nav item.
     *
     * @return Response
     */
    public function actionGetUtilitiesBadgeCount(): Response
    {
        $this->requireAcceptsJson();

        $badgeCount = 0;
        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        foreach ($utilities as $class) {
            /** @var UtilityInterface $class */
            $badgeCount += $class::badgeCount();
        }

        return $this->asJson([
            'badgeCount' => $badgeCount
        ]);
    }

    /**
     * Loads any CP alerts.
     *
     * @return Response
     */
    public function actionGetCpAlerts(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $path = Craft::$app->getRequest()->getRequiredBodyParam('path');

        // Fetch 'em and send 'em
        $alerts = Cp::alerts($path, true);

        return $this->asJson($alerts);
    }

    /**
     * Shuns a CP alert for 24 hours.
     *
     * @return Response
     */
    public function actionShunCpAlert(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $message = Craft::$app->getRequest()->getRequiredBodyParam('message');
        $user = Craft::$app->getUser()->getIdentity();

        $currentTime = DateTimeHelper::currentUTCDateTime();
        $tomorrow = $currentTime->add(new \DateInterval('P1D'));

        if (Craft::$app->getUsers()->shunMessageForUser($user->id, $message, $tomorrow)) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asErrorJson(Craft::t('app', 'An unknown error occurred.'));
    }

    /**
     * Tries a Craft edition on for size.
     *
     * @return Response
     * @throws BadRequestHttpException if Craft isn’t allowed to test edition upgrades
     */
    public function actionTryEdition(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $edition = Craft::$app->getRequest()->getRequiredBodyParam('edition');
        $licensedEdition = Craft::$app->getLicensedEdition();

        if ($licensedEdition === null) {
            $licensedEdition = 0;
        }

        switch ($edition) {
            case 'solo':
                $edition = Craft::Solo;
                break;
            case 'pro':
                $edition = Craft::Pro;
                break;
            default:
                throw new BadRequestHttpException('Invalid Craft edition: '.$edition);
        }

        // If this is actually an upgrade, make sure that they are allowed to test edition upgrades
        if ($edition > $licensedEdition && !Craft::$app->getCanTestEditions()) {
            throw new BadRequestHttpException('Craft is not permitted to test edition upgrades from this server');
        }

        Craft::$app->setEdition($edition);

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Switches Craft to the edition it's licensed for.
     *
     * @return Response
     */
    public function actionSwitchToLicensedEdition(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (Craft::$app->getHasWrongEdition()) {
            $licensedEdition = Craft::$app->getLicensedEdition();
            $success = Craft::$app->setEdition($licensedEdition);
        } else {
            // Just fake it
            $success = true;
        }

        return $this->asJson(['success' => $success]);
    }

    /**
     * Fetches plugin license statuses.
     *
     * @return Response
     */
    public function actionGetPluginLicenseInfo(): Response
    {
        // Update our records & use all licensed plugins as a starting point
        $licenseInfo = Craft::$app->getApi()->getLicenseInfo(['plugins']);
        $result = [];
        if (!empty($licenseInfo['pluginLicenses'])) {
            $defaultIconUrl = Craft::$app->getAssetManager()->getPublishedUrl('@app/icons/default-plugin.svg', true);
            foreach ($licenseInfo['pluginLicenses'] as $pluginLicenseInfo) {
                if (isset($pluginLicenseInfo['plugin'])) {
                    $pluginInfo = $pluginLicenseInfo['plugin'];
                    $result[$pluginInfo['handle']] = [
                        'isInstalled' => false,
                        'licenseKey' => $pluginLicenseInfo['key'],
                        'licenseKeyStatus' => LicenseKeyStatus::Valid,
                        'hasIssues' => false,
                        'licenseStatusMessage' => null,
                        'name' => $pluginInfo['name'],
                        'description' => $pluginInfo['shortDescription'],
                        'iconUrl' => $pluginInfo['icon']['url'] ?? $defaultIconUrl,
                        'documentationUrl' => $pluginInfo['documentationUrl'] ?? null,
                        'packageName' => $pluginInfo['packageName'],
                        'latestVersion' => $pluginInfo['latestVersion'],
                    ];
                }
            }
        }

        ArrayHelper::multisort($result, 'name');

        // Override with info for the installed plugins
        $info = Craft::$app->getPlugins()->getAllPluginInfo();
        foreach ($info as $handle => $pluginInfo) {
            $result[$handle] = [
                'isInstalled' => true,
                'licenseKey' => $pluginInfo['licenseKey'],
                'licenseKeyStatus' => $pluginInfo['licenseKeyStatus'],
                'hasIssues' => $pluginInfo['hasIssues'],
                'licenseStatusMessage' => $pluginInfo['licenseStatusMessage'],
            ];
        }

        return $this->asJson($result);
    }

    /**
     * Updates a plugin's license key.
     *
     * @return Response
     */
    public function actionUpdatePluginLicense(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();
        $handle = $request->getRequiredBodyParam('handle');
        $newKey = $request->getRequiredBodyParam('key');

        // Get the current key and set the new one
        $pluginsService = Craft::$app->getPlugins();
        $pluginsService->setPluginLicenseKey($handle, $newKey ?: null);

        // Update the status
        Craft::$app->getApi()->getLicenseInfo();

        // Return the new plugin license info
        $info = $pluginsService->getPluginInfo($handle);
        return $this->asJson([
            'licenseKey' => $info['licenseKey'],
            'licenseKeyStatus' => $info['licenseKeyStatus'],
            'hasIssues' => $info['hasIssues'],
            'licenseStatusMessage' => $info['licenseStatusMessage'],
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Transforms an update for inclusion in [[actionCheckForUpdates()]] response JSON.
     * Also sets an `allowed` key on the given update's releases, based on the `allowUpdates` config setting.
     *
     * @param bool $allowUpdates Whether updates are allowed
     * @param Update $update The update model
     * @param string $handle The handle of whatever this update is for
     * @param string $name The name of whatever this update is for
     * @return array
     */
    private function _transformUpdate(bool $allowUpdates, Update $update, string $handle, string $name): array
    {
        $arr = $update->toArray();
        $arr['handle'] = $handle;
        $arr['name'] = $name;
        $arr['latestVersion'] = $update->getLatest()->version ?? null;

        if ($update->status === Update::STATUS_EXPIRED) {
            $arr['statusText'] = Craft::t('app', '<strong>Your license has expired!</strong> Renew your {name} license for another year of amazing updates.', [
                'name' => $name
            ]);
            $arr['ctaText'] = Craft::t('app', 'Renew for {price}', [
                'price' => Craft::$app->getFormatter()->asCurrency($update->renewalPrice, $update->renewalCurrency)
            ]);
            $arr['ctaUrl'] = UrlHelper::url($update->renewalUrl);
        } else {
            if ($update->status === Update::STATUS_BREAKPOINT) {
                $arr['statusText'] = Craft::t('app', '<strong>You’ve reached a breakpoint!</strong> More updates will become available after you install {update}.</p>', [
                    'update' => $name.' '.($update->getLatest()->version ?? '')
                ]);
            }

            if ($allowUpdates) {
                $arr['ctaText'] = Craft::t('app', 'Update');
            }
        }

        return $arr;
    }
}
