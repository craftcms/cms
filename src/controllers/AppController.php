<?php
namespace Craft;

/**
 *
 */
class AppController extends BaseController
{
	/**
	 * Returns update info.
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
	 * Returns the editon upgrade modal.
	 */
	public function actionGetUpgradeModal()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		$etResponse = craft()->et->fetchEditionInfo();

		if ($etResponse)
		{
			// Make sure we've got a valid license key (mismatched domain is OK for these purposes)
			if ($etResponse->licenseKeyStatus != LicenseKeyStatus::Invalid)
			{
				$editions = array();

				foreach ($etResponse->data as $edition => $info)
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
					'canTestEditions' => $canTestEditions
				));

				$this->returnJson(array(
					'success'         => true,
					'editions'        => $editions,
					'canTestEditions' => $canTestEditions,
					'modalHtml'       => $modalHtml
				));
			}
			else
			{
				$this->returnErrorJson(Craft::t('Your license key is invalid.'));
			}
		}
		else
		{
			$this->returnErrorJson(Craft::t('Craft is unable to fetch edition info at this time.'));
		}
	}

	/**
	 * Passes along a given CC token to Elliott to purchase a Craft edition.
	 */
	public function actionPurchaseUpgrade()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		$model = new UpgradePurchaseModel(array(
			'ccTokenId'     => craft()->request->getRequiredPost('ccTokenId'),
			'edition'       => craft()->request->getRequiredPost('edition'),
			'expectedPrice' => craft()->request->getRequiredPost('expectedPrice'),
		));

		if (craft()->et->purchaseUpgrade($model))
		{
			$this->returnJson(array(
				'success' => true,
				'package' => $model->package
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
	 */
	public function actionTestUpgrade()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		if (!craft()->canTestEditions())
		{
			throw new Exception('Tried to test an edition, but Craft isn\'t allowed to do that.');
		}

		$edition = craft()->request->getRequiredPost('edition');
		craft()->setEdition($edition);

		$this->returnJson(array(
			'success' => true
		));
	}
}
