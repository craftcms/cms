<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\dates\DateInterval;
use craft\app\enums\LicenseKeyStatus;
use craft\app\errors\Exception;
use craft\app\helpers\AppHelper;
use craft\app\helpers\CpHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\UpgradePurchase  as UpgradePurchaseModel;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns update info.
	 *
	 * @return null
	 */
	public function actionCheckForUpdates()
	{
		$this->requirePermission('performUpdates');

		$forceRefresh = (bool) Craft::$app->request->getPost('forceRefresh');
		Craft::$app->updates->getUpdates($forceRefresh);

		$this->returnJson(array(
			'total'    => Craft::$app->updates->getTotalAvailableUpdates(),
			'critical' => Craft::$app->updates->isCriticalUpdateAvailable()
		));
	}

	/**
	 * Loads any CP alerts.
	 *
	 * @return null
	 */
	public function actionGetCpAlerts()
	{
		$this->requireAjaxRequest();
		$this->requirePermission('accessCp');

		$path = Craft::$app->request->getRequiredPost('path');

		// Fetch 'em and send 'em
		$alerts = CpHelper::getAlerts($path, true);
		$this->returnJson($alerts);
	}

	/**
	 * Shuns a CP alert for 24 hours.
	 *
	 * @return null
	 */
	public function actionShunCpAlert()
	{
		$this->requireAjaxRequest();
		$this->requirePermission('accessCp');

		$message = Craft::$app->request->getRequiredPost('message');
		$user = Craft::$app->getUser()->getIdentity();

		$currentTime = DateTimeHelper::currentUTCDateTime();
		$tomorrow = $currentTime->add(new DateInterval('P1D'));

		if (Craft::$app->users->shunMessageForUser($user->id, $message, $tomorrow))
		{
			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			$this->returnErrorJson(Craft::t('An unknown error occurred.'));
		}
	}

	/**
	 * Transfers the Craft license to the current domain.
	 *
	 * @return null
	 */
	public function actionTransferLicenseToCurrentDomain()
	{
		$this->requireAjaxRequest();
		$this->requirePostRequest();
		$this->requireAdmin();

		$response = Craft::$app->et->transferLicenseToCurrentDomain();

		if ($response === true)
		{
			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			$this->returnErrorJson($response);
		}
	}

	/**
	 * Returns the edition upgrade modal.
	 *
	 * @return null
	 */
	public function actionGetUpgradeModal()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$etResponse = Craft::$app->et->fetchEditionInfo();

		if (!$etResponse)
		{
			$this->returnErrorJson(Craft::t('Craft is unable to fetch edition info at this time.'));
		}

		// Make sure we've got a valid license key (mismatched domain is OK for these purposes)
		if ($etResponse->licenseKeyStatus == LicenseKeyStatus::Invalid)
		{
			$this->returnErrorJson(Craft::t('Your license key is invalid.'));
		}

		// Make sure they've got a valid licensed edition, just to be safe
		if (!AppHelper::isValidEdition($etResponse->licensedEdition))
		{
			$this->returnErrorJson(Craft::t('Your license has an invalid Craft edition associated with it.'));
		}

		$editions = array();

		foreach ($etResponse->data as $edition => $info)
		{
			$editions[$edition]['price']          = $info['price'];
			$editions[$edition]['formattedPrice'] = Craft::$app->numberFormatter->formatCurrency($info['price'], 'USD', true);

			if (isset($info['salePrice']) && $info['salePrice'] < $info['price'])
			{
				$editions[$edition]['salePrice']          = $info['salePrice'];
				$editions[$edition]['formattedSalePrice'] = Craft::$app->numberFormatter->formatCurrency($info['salePrice'], 'USD', true);
			}
			else
			{
				$editions[$edition]['salePrice'] = null;
			}
		}

		$canTestEditions = Craft::$app->canTestEditions();

		$modalHtml = Craft::$app->templates->render('_upgrademodal', array(
			'editions'        => $editions,
			'licensedEdition' => $etResponse->licensedEdition,
			'canTestEditions' => $canTestEditions
		));

		$this->returnJson(array(
			'success'         => true,
			'editions'        => $editions,
			'licensedEdition' => $etResponse->licensedEdition,
			'canTestEditions' => $canTestEditions,
			'modalHtml'       => $modalHtml
		));
	}

	/**
	 * Passes along a given CC token to Elliott to purchase a Craft edition.
	 *
	 * @return null
	 */
	public function actionPurchaseUpgrade()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$model = new UpgradePurchaseModel(array(
			'ccTokenId'     => Craft::$app->request->getRequiredPost('ccTokenId'),
			'edition'       => Craft::$app->request->getRequiredPost('edition'),
			'expectedPrice' => Craft::$app->request->getRequiredPost('expectedPrice'),
		));

		if (Craft::$app->et->purchaseUpgrade($model))
		{
			$this->returnJson(array(
				'success' => true,
				'edition' => $model->edition
			));
		}
		else
		{
			$this->returnJson(array(
				'errors' => $model->getErrors()
			));
		}
	}

	/**
	 * Tries a Craft edition on for size.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionTestUpgrade()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		$this->requireAdmin();

		if (!Craft::$app->canTestEditions())
		{
			throw new Exception('Tried to test an edition, but Craft isn\'t allowed to do that.');
		}

		$edition = Craft::$app->request->getRequiredPost('edition');
		Craft::$app->setEdition($edition);

		$this->returnJson(array(
			'success' => true
		));
	}

	/**
	 * Switches Craft to the edition it's licensed for.
	 *
	 * @return null
	 */
	public function actionSwitchToLicensedEdition()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (Craft::$app->hasWrongEdition())
		{
			$licensedEdition = Craft::$app->getLicensedEdition();
			$success = Craft::$app->setEdition($licensedEdition);
		}
		else
		{
			// Just fake it
			$success = true;
		}

		$this->returnJson(array('success' => $success));
	}
}
