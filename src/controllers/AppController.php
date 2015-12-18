<?php
namespace Craft;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
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
		craft()->userSession->requirePermission('performUpdates');

		$forceRefresh = (bool) craft()->request->getPost('forceRefresh');
		craft()->updates->getUpdates($forceRefresh);

		$this->returnJson(array(
			'total'    => craft()->updates->getTotalAvailableUpdates(),
			'critical' => craft()->updates->isCriticalUpdateAvailable()
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
		craft()->userSession->requirePermission('accessCp');

		$path = craft()->request->getRequiredPost('path');

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
		craft()->userSession->requirePermission('accessCp');

		$message = craft()->request->getRequiredPost('message');
		$user = craft()->userSession->getUser();

		$currentTime = DateTimeHelper::currentUTCDateTime();
		$tomorrow = $currentTime->add(new DateInterval('P1D'));

		if (craft()->users->shunMessageForUser($user->id, $message, $tomorrow))
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
		craft()->userSession->requireAdmin();

		$response = craft()->et->transferLicenseToCurrentDomain();

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

		// Make it so Craft Client accounts can perform the upgrade.
		if (craft()->getEdition() == Craft::Pro)
		{
			craft()->userSession->requireAdmin();
		}

		$etResponse = craft()->et->fetchUpgradeInfo();

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

		foreach ($etResponse->data->editions as $edition => $info)
		{
			$editions[$edition]['price']          = $info['price'];
			$editions[$edition]['formattedPrice'] = craft()->numberFormatter->formatCurrency($info['price'], 'USD', true);

			if (isset($info['salePrice']) && $info['salePrice'] < $info['price'])
			{
				$editions[$edition]['salePrice']          = $info['salePrice'];
				$editions[$edition]['formattedSalePrice'] = craft()->numberFormatter->formatCurrency($info['salePrice'], 'USD', true);
			}
			else
			{
				$editions[$edition]['salePrice'] = null;
			}
		}

		$canTestEditions = craft()->canTestEditions();

		$modalHtml = craft()->templates->render('_upgrademodal', array(
			'editions'        => $editions,
			'licensedEdition' => $etResponse->licensedEdition,
			'canTestEditions' => $canTestEditions
		));

		$this->returnJson(array(
			'success'         => true,
			'editions'        => $editions,
			'licensedEdition' => $etResponse->licensedEdition,
			'canTestEditions' => $canTestEditions,
			'modalHtml'       => $modalHtml,
			'stripePublicKey' => $etResponse->data->stripePublicKey,
			'countries'       => $etResponse->data->countries,
			'states'          => $etResponse->data->states,
		));
	}

	/**
	 * Returns the price of an upgrade with a coupon applied to it.
	 *
	 * @return void
	 */
	public function actionGetCouponPrice()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Make it so Craft Client accounts can perform the upgrade.
		if (craft()->getEdition() == Craft::Pro)
		{
			craft()->userSession->requireAdmin();
		}

		$edition = craft()->request->getRequiredPost('edition');
		$couponCode = craft()->request->getRequiredPost('couponCode');

		$etResponse = craft()->et->fetchCouponPrice($edition, $couponCode);

		if (!empty($etResponse->data['success']))
		{
			$couponPrice = $etResponse->data['couponPrice'];
			$formattedCouponPrice = craft()->numberFormatter->formatCurrency($couponPrice, 'USD', true);

			$this->returnJson(array(
				'success' => true,
				'couponPrice' => $couponPrice,
				'formattedCouponPrice' => $formattedCouponPrice
			));
		}
		else
		{
			$this->returnJson(array(
				'success' => false
			));
		}
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

		// Make it so Craft Client accounts can perform the upgrade.
		if (craft()->getEdition() == Craft::Pro)
		{
			craft()->userSession->requireAdmin();
		}

		$model = new UpgradePurchaseModel(array(
			'ccTokenId'        => craft()->request->getRequiredPost('ccTokenId'),
			'expMonth'         => craft()->request->getRequiredPost('expMonth'),
			'expYear'          => craft()->request->getRequiredPost('expYear'),
			'edition'          => craft()->request->getRequiredPost('edition'),
			'expectedPrice'    => craft()->request->getRequiredPost('expectedPrice'),
			'name'             => craft()->request->getRequiredPost('name'),
			'email'            => craft()->request->getRequiredPost('email'),
			'businessName'     => craft()->request->getPost('businessName'),
			'businessAddress1' => craft()->request->getPost('businessAddress1'),
			'businessAddress2' => craft()->request->getPost('businessAddress2'),
			'businessCity'     => craft()->request->getPost('businessCity'),
			'businessState'    => craft()->request->getPost('businessState'),
			'businessCountry'  => craft()->request->getPost('businessCountry'),
			'businessZip'      => craft()->request->getPost('businessZip'),
			'businessTaxId'    => craft()->request->getPost('businessTaxId'),
			'purchaseNotes'    => craft()->request->getPost('purchaseNotes'),
			'couponCode'       => craft()->request->getPost('couponCode'),
		));

		if (craft()->et->purchaseUpgrade($model))
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
		craft()->userSession->requireAdmin();

		$edition = craft()->request->getRequiredPost('edition');
		$licensedEdition = craft()->getLicensedEdition();

		if ($licensedEdition === null)
		{
			$licensedEdition = 0;
		}

		// If this is actually an upgrade, make sure that they are allowed to test edition upgrades
		if ($edition > $licensedEdition && !craft()->canTestEditions())
		{
			throw new Exception('Tried to test an edition, but Craft isn\'t allowed to do that.');
		}

		craft()->setEdition($edition);

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

		if (craft()->hasWrongEdition())
		{
			$licensedEdition = craft()->getLicensedEdition();
			$success = craft()->setEdition($licensedEdition);
		}
		else
		{
			// Just fake it
			$success = true;
		}

		$this->returnJson(array('success' => $success));
	}
}
