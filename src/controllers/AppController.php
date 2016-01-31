<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\enums\LicenseKeyStatus;
use craft\app\errors\Exception;
use craft\app\helpers\App;
use craft\app\helpers\Cp;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\UpgradeInfo;
use craft\app\models\UpgradePurchase as UpgradePurchaseModel;
use craft\app\web\Controller;
use yii\web\Response;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Returns update info.
     *
     * @return Response
     */
    public function actionCheckForUpdates()
    {
        $this->requirePermission('performUpdates');

        $forceRefresh = (bool)Craft::$app->getRequest()->getBodyParam('forceRefresh');
        Craft::$app->getUpdates()->getUpdates($forceRefresh);

        return $this->asJson([
            'total' => Craft::$app->getUpdates()->getTotalAvailableUpdates(),
            'critical' => Craft::$app->getUpdates()->isCriticalUpdateAvailable()
        ]);
    }

    /**
     * Loads any CP alerts.
     *
     * @return Response
     */
    public function actionGetCpAlerts()
    {
        $this->requireAjaxRequest();
        $this->requirePermission('accessCp');

        $path = Craft::$app->getRequest()->getRequiredBodyParam('path');

        // Fetch 'em and send 'em
        $alerts = Cp::getAlerts($path, true);

        return $this->asJson($alerts);
    }

    /**
     * Shuns a CP alert for 24 hours.
     *
     * @return Response
     */
    public function actionShunCpAlert()
    {
        $this->requireAjaxRequest();
        $this->requirePermission('accessCp');

        $message = Craft::$app->getRequest()->getRequiredBodyParam('message');
        $user = Craft::$app->getUser()->getIdentity();

        $currentTime = DateTimeHelper::currentUTCDateTime();
        $tomorrow = $currentTime->add(new DateInterval('P1D'));

        if (Craft::$app->getUsers()->shunMessageForUser($user->id, $message, $tomorrow)) {
            return $this->asJson([
                'success' => true
            ]);
        } else {
            return $this->asErrorJson(Craft::t('app', 'An unknown error occurred.'));
        }
    }

    /**
     * Transfers the Craft license to the current domain.
     *
     * @return Response
     */
    public function actionTransferLicenseToCurrentDomain()
    {
        $this->requireAjaxRequest();
        $this->requirePostRequest();
        $this->requireAdmin();

        $response = Craft::$app->getEt()->transferLicenseToCurrentDomain();

        if ($response === true) {
            return $this->asJson([
                'success' => true
            ]);
        } else {
            return $this->asErrorJson($response);
        }
    }

    /**
     * Returns the edition upgrade modal.
     *
     * @return Response
     */
    public function actionGetUpgradeModal()
    {
        $this->requireAjaxRequest();

        // Make it so Craft Client accounts can perform the upgrade.
        if (Craft::$app->getEdition() == Craft::Pro) {
            $this->requireAdmin();
        }

        $etResponse = Craft::$app->getEt()->fetchUpgradeInfo();

        if (!$etResponse) {
            return $this->asErrorJson(Craft::t('app', 'Craft is unable to fetch edition info at this time.'));
        }

        // Make sure we've got a valid license key (mismatched domain is OK for these purposes)
        if ($etResponse->licenseKeyStatus == LicenseKeyStatus::Invalid) {
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

        $canTestEditions = Craft::$app->canTestEditions();

        $modalHtml = Craft::$app->getView()->renderTemplate('_upgrademodal', [
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
        ]);
    }

    /**
     * Passes along a given CC token to Elliott to purchase a Craft edition.
     *
     * @return Response
     */
    public function actionPurchaseUpgrade()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        // Make it so Craft Client accounts can perform the upgrade.
        if (Craft::$app->getEdition() == Craft::Pro) {
            $this->requireAdmin();
        }

        $model = new UpgradePurchaseModel([
            'ccTokenId' => Craft::$app->getRequest()->getRequiredBodyParam('ccTokenId'),
            'edition' => Craft::$app->getRequest()->getRequiredBodyParam('edition'),
            'expectedPrice' => Craft::$app->getRequest()->getRequiredBodyParam('expectedPrice'),
        ]);

        if (Craft::$app->getEt()->purchaseUpgrade($model)) {
            return $this->asJson([
                'success' => true,
                'edition' => $model->edition
            ]);
        } else {
            return $this->asJson([
                'errors' => $model->getErrors()
            ]);
        }
    }

    /**
     * Tries a Craft edition on for size.
     *
     * @throws Exception
     * @return Response
     */
    public function actionTestUpgrade()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $this->requireAdmin();

        if (!Craft::$app->canTestEditions()) {
            throw new Exception('Tried to test an edition, but Craft isn\'t allowed to do that.');
        }

        $edition = Craft::$app->getRequest()->getRequiredBodyParam('edition');
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
    public function actionSwitchToLicensedEdition()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        if (Craft::$app->hasWrongEdition()) {
            $licensedEdition = Craft::$app->getLicensedEdition();
            $success = Craft::$app->setEdition($licensedEdition);
        } else {
            // Just fake it
            $success = true;
        }

        return $this->asJson(['success' => $success]);
    }
}
