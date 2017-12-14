<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\base\UtilityInterface;
use craft\config\GeneralConfig;
use craft\enums\LicenseKeyStatus;
use craft\errors\MigrationException;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Update;
use craft\models\UpgradeInfo;
use craft\models\UpgradePurchase;
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
 *
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

        $res = [
            'total' => $updates->getTotal(),
            'critical' => $updates->getHasCritical(),
        ];

        if ($includeDetails) {
            $res['updates'] = [
                'cms' => $this->_transformUpdate($updates->cms, 'craft', 'Craft CMS', Craft::$app->getVersion()),
                'plugins' => [],
            ];

            $pluginsService = Craft::$app->getPlugins();
            foreach ($updates->plugins as $handle => $update) {
                if (($plugin = $pluginsService->getPlugin($handle)) !== null) {
                    /** @var Plugin $plugin */
                    $res['updates']['plugins'][] = $this->_transformUpdate($update, $handle, $plugin->name, $plugin->getVersion());
                }
            }
        }

        return $this->asJson($res);
    }

    /**
     * Creates a DB backup (if configured to do so) and runs any pending Craft, plugin, & content migrations in one go.
     *
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
     * Transfers the Craft license to the current domain.
     *
     * @return Response
     */
    public function actionTransferLicenseToCurrentDomain(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireAdmin();

        $response = Craft::$app->getEt()->transferLicenseToCurrentDomain();

        if ($response === true) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asErrorJson($response);
    }

    /**
     * Returns the edition upgrade modal.
     *
     * @return Response
     */
    public function actionGetUpgradeModal(): Response
    {
        $this->requireAcceptsJson();

        // Make it so Craft Client accounts can perform the upgrade.
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->requireAdmin();
        }

        $etResponse = Craft::$app->getEt()->fetchUpgradeInfo();

        if (!$etResponse) {
            return $this->asErrorJson(Craft::t('app', 'Craft is unable to fetch edition info at this time.'));
        }

        // Make sure we've got a valid license key (mismatched domain is OK for these purposes)
        if ($etResponse->licenseKeyStatus === LicenseKeyStatus::Invalid) {
            return $this->asErrorJson(Craft::t('app', 'Your license key is invalid.'));
        }

        // Make sure they've got a valid licensed edition, just to be safe
        if (!App::isValidEdition($etResponse->licensedEdition)) {
            return $this->asErrorJson(Craft::t('app', 'Your license has an invalid Craft edition associated with it.'));
        }

        $editions = [];
        $formatter = Craft::$app->getFormatter();

        /** @var UpgradeInfo $upgradeInfo */
        $upgradeInfo = $etResponse->data;

        foreach ($upgradeInfo->editions as $edition => $info) {
            $editions[$edition]['price'] = $info['price'];
            $editions[$edition]['formattedPrice'] = $formatter->asCurrency($info['price'], 'USD', [], [], true);

            if (isset($info['salePrice']) && $info['salePrice'] < $info['price']) {
                $editions[$edition]['salePrice'] = $info['salePrice'];
                $editions[$edition]['formattedSalePrice'] = $formatter->asCurrency($info['salePrice'], 'USD', [], [], true);
            } else {
                $editions[$edition]['salePrice'] = null;
            }
        }

        $canTestEditions = Craft::$app->getCanTestEditions();

        $modalHtml = $this->getView()->renderTemplate('_upgrademodal', [
            'editions' => $editions,
            'licensedEdition' => $etResponse->licensedEdition,
            'canTestEditions' => $canTestEditions
        ]);

        return $this->asJson([
            'success' => true,
            'editions' => $editions,
            'licensedEdition' => $etResponse->licensedEdition,
            'canTestEditions' => $canTestEditions,
            'modalHtml' => $modalHtml,
            'stripePublicKey' => $upgradeInfo->stripePublicKey,
            'countries' => $upgradeInfo->countries,
            'states' => $upgradeInfo->states,
        ]);
    }

    /**
     * Returns the price of an upgrade with a coupon applied to it.
     *
     * @return Response
     */
    public function actionGetCouponPrice(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Make it so Craft Client accounts can perform the upgrade.
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->requireAdmin();
        }

        $request = Craft::$app->getRequest();
        $edition = $request->getRequiredBodyParam('edition');
        $couponCode = $request->getRequiredBodyParam('couponCode');

        $etResponse = Craft::$app->getEt()->fetchCouponPrice($edition, $couponCode);

        if (!empty($etResponse->data['success'])) {
            $couponPrice = $etResponse->data['couponPrice'];
            $formattedCouponPrice = Craft::$app->getFormatter()->asCurrency($couponPrice, 'USD', [], [], true);

            return $this->asJson([
                'success' => true,
                'couponPrice' => $couponPrice,
                'formattedCouponPrice' => $formattedCouponPrice
            ]);
        }

        return $this->asJson([
            'success' => false
        ]);
    }

    /**
     * Passes along a given CC token to Elliott to purchase a Craft edition.
     *
     * @return Response
     */
    public function actionPurchaseUpgrade(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Make it so Craft Client accounts can perform the upgrade.
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->requireAdmin();
        }

        $request = Craft::$app->getRequest();
        $model = new UpgradePurchase([
            'ccTokenId' => $request->getRequiredBodyParam('ccTokenId'),
            'expMonth' => $request->getRequiredBodyParam('expMonth'),
            'expYear' => $request->getRequiredBodyParam('expYear'),
            'edition' => $request->getRequiredBodyParam('edition'),
            'expectedPrice' => $request->getRequiredBodyParam('expectedPrice'),
            'name' => $request->getRequiredBodyParam('name'),
            'email' => $request->getRequiredBodyParam('email'),
            'businessName' => $request->getBodyParam('businessName'),
            'businessAddress1' => $request->getBodyParam('businessAddress1'),
            'businessAddress2' => $request->getBodyParam('businessAddress2'),
            'businessCity' => $request->getBodyParam('businessCity'),
            'businessState' => $request->getBodyParam('businessState'),
            'businessCountry' => $request->getBodyParam('businessCountry'),
            'businessZip' => $request->getBodyParam('businessZip'),
            'businessTaxId' => $request->getBodyParam('businessTaxId'),
            'purchaseNotes' => $request->getBodyParam('purchaseNotes'),
            'couponCode' => $request->getBodyParam('couponCode'),
        ]);

        if (Craft::$app->getEt()->purchaseUpgrade($model)) {
            return $this->asJson([
                'success' => true,
                'edition' => $model->edition
            ]);
        }

        return $this->asJson([
            'errors' => $model->getErrors()
        ]);
    }

    /**
     * Tries a Craft edition on for size.
     *
     * @return Response
     * @throws BadRequestHttpException if Craft isn’t allowed to test edition upgrades
     */
    public function actionTestUpgrade(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $edition = Craft::$app->getRequest()->getRequiredBodyParam('edition');
        $licensedEdition = Craft::$app->getLicensedEdition();

        if ($licensedEdition === null) {
            $licensedEdition = 0;
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

    // Private Methods
    // =========================================================================

    /**
     * Transforms an update for inclusion in [[actionCheckForUpdates()]] response JSON.
     *
     * Also sets an `allowed` key on the given update's releases, based on the `allowAutoUpdates` config setting.
     *
     * @param Update $update         The update model
     * @param string $handle         The handle of whatever this update is for
     * @param string $name           The name of whatever this update is for
     * @param string $currentVersion The current version of whatever this update is for
     *
     * @return array
     */
    private function _transformUpdate(Update $update, string $handle, string $name, string $currentVersion): array
    {
        $arr = $update->toArray();
        $arr['handle'] = $handle;
        $arr['name'] = $name;
        $arr['latestAllowedVersion'] = $this->_latestAllowedVersion($update, $currentVersion);

        switch ($update->status) {
            case Update::STATUS_EXPIRED:
                $arr['statusText'] = Craft::t('app', '<strong>Your license has expired!</strong> Renew your {name} license for another year of amazing updates.', [
                    'name' => $name
                ]);
                $arr['ctaText'] = Craft::t('app', 'Renew for {price}', [
                    'price' => Craft::$app->getFormatter()->asCurrency($update->renewalPrice, $update->renewalCurrency)
                ]);
                $arr['ctaUrl'] = UrlHelper::url($update->renewalUrl);
                break;
            case Update::STATUS_BREAKPOINT:
                $arr['statusText'] = Craft::t('app', '<strong>You’ve reached a breakpoint!</strong> More updates will become available after you install {update}.</p>', [
                    'update' => $name.' '.($update->getLatest()->version ?? '')
                ]);
            // no break
            default:
                if ($arr['latestAllowedVersion'] !== null && $arr['latestAllowedVersion'] === $update->getLatest()->version) {
                    $arr['ctaText'] = Craft::t('app', 'Update');
                } else {
                    $arr['ctaText'] = Craft::t('app', 'Update to {version}', [
                        'version' => $arr['latestAllowedVersion']
                    ]);
                }
        }

        // Find the latest release that we're actually allowed to update to


        return $arr;
    }

    /**
     * Returns the latest version that the user is allowed to update to, per the
     * `performUpdates` permission and `allowAutoUpdates` config setting.
     *
     * @param Update $update
     * @param string $currentVersion
     *
     * @return string|null
     */
    private function _latestAllowedVersion(Update $update, string $currentVersion)
    {
        if (Craft::$app->getUser()->checkPermission('performUpdates')) {
            $allowAutoUpdates = Craft::$app->getConfig()->getGeneral()->allowAutoUpdates;
        } else {
            $allowAutoUpdates = false;
        }

        $arr['latestAllowedVersion'] = null;

        if ($allowAutoUpdates === true) {
            return $update->getLatest()->version ?? null;
        }

        if ($allowAutoUpdates === GeneralConfig::AUTO_UPDATE_PATCH_ONLY) {
            $currentMajorMinor = App::majorMinorVersion($currentVersion);
            foreach ($update->releases as $release) {
                if (App::majorMinorVersion($release->version) === $currentMajorMinor) {
                    return $release->version;
                }
            }
        } else if ($allowAutoUpdates === GeneralConfig::AUTO_UPDATE_MINOR_ONLY) {
            $currentMajor = App::majorVersion($currentVersion);
            foreach ($update->releases as $release) {
                if (App::majorVersion($release->version) === $currentMajor) {
                    return $release->version;
                }
            }
        }

        return null;
    }
}
