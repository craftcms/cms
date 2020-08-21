<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\UtilityInterface;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidPluginException;
use craft\helpers\Api;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Update;
use craft\models\Updates;
use craft\web\Controller;
use craft\web\ServiceUnavailableHttpException;
use Http\Client\Common\Exception\ServerErrorException;
use yii\base\InvalidConfigException;
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
 * @since 3.0.0
 * @internal
 */
class AppController extends Controller
{
    /**
     * @inheritdoc
     */
    public $allowAnonymous = [
        'migrate' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'broken-image' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'health-check' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id === 'migrate') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Returns an empty response.
     *
     * @since 3.5.0
     */
    public function actionHealthCheck(): Response
    {
        // All that matters is the 200 response
        $this->response->format = Response::FORMAT_RAW;
        $this->response->data = '';
        return $this->response;
    }

    /**
     * Returns the latest Craftnet API headers.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.3.16
     */
    public function actionApiHeaders(): Response
    {
        $this->requireCpRequest();
        return $this->asJson(Api::headers());
    }

    /**
     * Processes an API response’s headers.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.3.16
     */
    public function actionProcessApiResponseHeaders()
    {
        $this->requireCpRequest();
        $headers = $this->request->getRequiredBodyParam('headers');
        Api::processResponseHeaders($headers);
        return $this->asJson(1);
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
        $userSession = Craft::$app->getUser();
        if (!$userSession->checkPermission('performUpdates') && !$userSession->checkPermission('utility:updates')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $updatesService = Craft::$app->getUpdates();

        if ($this->request->getParam('onlyIfCached') && !$updatesService->getIsUpdateInfoCached()) {
            return $this->asJson(['cached' => false]);
        }

        $forceRefresh = (bool)$this->request->getParam('forceRefresh');
        $includeDetails = (bool)$this->request->getParam('includeDetails');

        $updates = $updatesService->getUpdates($forceRefresh);
        return $this->_updatesResponse($updates, $includeDetails);
    }

    /**
     * Caches new update info and then returns it.
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @since 3.3.16
     */
    public function actionCacheUpdates(): Response
    {
        $this->requireAcceptsJson();

        // Require either the 'performUpdates' or 'utility:updates' permission
        $userSession = Craft::$app->getUser();
        if (!$userSession->checkPermission('performUpdates') && !$userSession->checkPermission('utility:updates')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $updateData = $this->request->getBodyParam('updates');
        $updatesService = Craft::$app->getUpdates();
        $updates = $updatesService->cacheUpdates($updateData);
        $includeDetails = (bool)$this->request->getParam('includeDetails');
        return $this->_updatesResponse($updates, $includeDetails);
    }

    /**
     * Returns updates info as JSON
     *
     * @param Updates $updates The updates model
     * @param bool $includeDetails Whether to include update details
     * @return Response
     */
    private function _updatesResponse(Updates $updates, bool $includeDetails): Response
    {
        $allowUpdates = (
            Craft::$app->getConfig()->getGeneral()->allowUpdates &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges &&
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
            foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
                try {
                    $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                } catch (InvalidPluginException $e) {
                    continue;
                }
                $res['updates']['plugins'][] = $this->_transformUpdate($allowUpdates, $pluginUpdate, $pluginHandle, $pluginInfo['name']);
            }
        }

        return $this->asJson($res);
    }

    /**
     * Creates a DB backup (if configured to do so), runs any pending Craft,
     * plugin, & content migrations, and syncs `project.yaml` changes in one go.
     *
     * This action can be used as a post-deploy webhook with site deployment
     * services (like [DeployBot](https://deploybot.com/) or [DeployPlace](https://deployplace.com/)) to minimize site
     * downtime after a deployment.
     *
     * @param bool $applyProjectConfigChanges
     * @throws ServerErrorException if something went wrong
     */
    public function actionMigrate(bool $applyProjectConfigChanges = false)
    {
        $this->requirePostRequest();

        $updatesService = Craft::$app->getUpdates();
        $db = Craft::$app->getDb();

        // Get the handles in need of an update
        $handles = $updatesService->getPendingMigrationHandles(true);
        $runMigrations = !empty($handles);

        $projectConfigService = Craft::$app->getProjectConfig();
        if ($applyProjectConfigChanges) {
            $applyProjectConfigChanges = $projectConfigService->areChangesPending();
        }

        if (!$runMigrations && !$applyProjectConfigChanges) {
            // That was easy
            return $this->response;
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

        $transaction = $db->beginTransaction();

        try {
            // Run the migrations?
            if ($runMigrations) {
                $updatesService->runMigrations($handles);
            }

            // Sync project.yaml?
            if ($applyProjectConfigChanges) {
                $projectConfigService->applyYamlChanges();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            // MySQL may have implicitly committed the transaction
            $restored = $db->getIsPgsql();

            // Do we have a backup?
            if (!$restored && !empty($backupPath)) {
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
                $error .= ' The database could not be restored due to a separate error: ' . $restoreException->getMessage();
            } else {
                $error .= ' The database has not been restored.';
            }

            Craft::$app->disableMaintenanceMode();
            throw new ServerErrorHttpException($error, 0, $e);
        }

        Craft::$app->disableMaintenanceMode();
        return $this->response;
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
     * Returns any alerts that should be displayed in the control panel.
     *
     * @return Response
     */
    public function actionGetCpAlerts(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $path = $this->request->getRequiredBodyParam('path');

        // Fetch 'em and send 'em
        $alerts = Cp::alerts($path, true);

        return $this->asJson($alerts);
    }

    /**
     * Shuns a control panel alert for 24 hours.
     *
     * @return Response
     */
    public function actionShunCpAlert(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $message = $this->request->getRequiredBodyParam('message');
        $user = Craft::$app->getUser()->getIdentity();

        $currentTime = DateTimeHelper::currentUTCDateTime();
        $tomorrow = $currentTime->add(new \DateInterval('P1D'));

        if (Craft::$app->getUsers()->shunMessageForUser($user->id, $message, $tomorrow)) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asErrorJson(Craft::t('app', 'A server error occurred.'));
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

        $edition = $this->request->getRequiredBodyParam('edition');
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
                throw new BadRequestHttpException('Invalid Craft edition: ' . $edition);
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
        $this->requireAdmin();
        $pluginLicenses = $this->request->getBodyParam('pluginLicenses');
        $result = $this->_pluginLicenseInfo($pluginLicenses);
        ArrayHelper::multisort($result, 'name');
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

        $handle = $this->request->getRequiredBodyParam('handle');
        $newKey = $this->request->getRequiredBodyParam('key');

        // Get the current key and set the new one
        $pluginsService = Craft::$app->getPlugins();
        $pluginsService->setPluginLicenseKey($handle, $newKey ?: null);

        // Return the new plugin license info
        return $this->asJson(1);
    }

    /**
     * Transforms an update for inclusion in [[actionCheckForUpdates()]] response JSON.
     *
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
                $arr['statusText'] = Craft::t('app', '<strong>You’ve reached a breakpoint!</strong> More updates will become available after you install {update}.', [
                    'update' => $name . ' ' . ($update->getLatest()->version ?? '')
                ]);
            }

            if ($allowUpdates) {
                $arr['ctaText'] = Craft::t('app', 'Update');
            }
        }

        return $arr;
    }

    /**
     * Returns plugin license info.
     *
     * @param array|null $pluginLicenses
     * @return array
     */
    private function _pluginLicenseInfo(array $pluginLicenses = null): array
    {
        $result = [];

        if ($pluginLicenses === null) {
            // Update our records and get license info from the API
            $licenseInfo = Craft::$app->getApi()->getLicenseInfo(['plugins']);
            $pluginLicenses = $licenseInfo['pluginLicenses'] ?? [];
        }

        $pluginsService = Craft::$app->getPlugins();
        $allPluginInfo = $pluginsService->getAllPluginInfo();

        // Update our records & use all licensed plugins as a starting point
        if (!empty($pluginLicenses)) {
            $defaultIconUrl = Craft::$app->getAssetManager()->getPublishedUrl('@app/icons/default-plugin.svg', true);
            $formatter = Craft::$app->getFormatter();
            foreach ($pluginLicenses as $pluginLicenseInfo) {
                if (isset($pluginLicenseInfo['plugin'])) {
                    $pluginInfo = $pluginLicenseInfo['plugin'];
                    $handle = $pluginInfo['handle'];

                    // The same plugin could be associated with this Craft license more than once,
                    // so make sure this is the same license they've entered a license key for, if there is one
                    if (
                        !isset($allPluginInfo[$handle]) ||
                        !$allPluginInfo[$handle]['licenseKey'] ||
                        $pluginsService->normalizePluginLicenseKey(Craft::parseEnv($allPluginInfo[$handle]['licenseKey'])) === $pluginLicenseInfo['key']
                    ) {
                        $result[$handle] = [
                            'edition' => null,
                            'isComposerInstalled' => false,
                            'isInstalled' => false,
                            'isEnabled' => false,
                            'licenseKey' => $pluginLicenseInfo['key'],
                            'licensedEdition' => $pluginLicenseInfo['edition'],
                            'licenseKeyStatus' => LicenseKeyStatus::Valid,
                            'licenseIssues' => [],
                            'name' => $pluginInfo['name'],
                            'description' => $pluginInfo['shortDescription'],
                            'iconUrl' => $pluginInfo['icon']['url'] ?? $defaultIconUrl,
                            'documentationUrl' => $pluginInfo['documentationUrl'] ?? null,
                            'packageName' => $pluginInfo['packageName'],
                            'latestVersion' => $pluginInfo['latestVersion'],
                            'expired' => $pluginLicenseInfo['expired'],
                        ];
                        if ($pluginLicenseInfo['expired']) {
                            $result[$handle]['renewalUrl'] = $pluginLicenseInfo['renewalUrl'];
                            $result[$handle]['renewalText'] = Craft::t('app', 'Renew for {price}', [
                                'price' => $formatter->asCurrency($pluginLicenseInfo['renewalPrice'], $pluginLicenseInfo['renewalCurrency'])
                            ]);
                        }
                    }
                }
            }
        }

        // Override with info for the installed plugins
        foreach ($allPluginInfo as $handle => $pluginInfo) {
            $result[$handle] = array_merge($result[$handle] ?? [], [
                'isComposerInstalled' => true,
                'isInstalled' => $pluginInfo['isInstalled'],
                'isEnabled' => $pluginInfo['isEnabled'],
                'version' => $pluginInfo['version'],
                'hasMultipleEditions' => $pluginInfo['hasMultipleEditions'],
                'edition' => $pluginInfo['edition'],
                'licenseKey' => $pluginsService->normalizePluginLicenseKey(Craft::parseEnv($pluginInfo['licenseKey'])),
                'licensedEdition' => $pluginInfo['licensedEdition'],
                'licenseKeyStatus' => $pluginInfo['licenseKeyStatus'],
                'licenseIssues' => $pluginInfo['licenseIssues'],
                'isTrial' => $pluginInfo['isTrial'],
                'upgradeAvailable' => $pluginInfo['upgradeAvailable'],
            ]);
        }

        return $result;
    }

    /**
     * Sends a broken image.
     *
     * @return Response
     * @throws InvalidConfigException
     * @since 3.5.0
     */
    public function actionBrokenImage(): Response
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $imagePath = Craft::getAlias($generalConfig->brokenImagePath);
        if (!is_file($imagePath)) {
            throw new InvalidConfigException("Invalid broken image path: $generalConfig->brokenImagePath");
        }

        $statusCode = $this->response->getStatusCode();
        return $this->response
            ->sendFile($imagePath, ['inline' => true])
            ->setStatusCode($statusCode);
    }
}
