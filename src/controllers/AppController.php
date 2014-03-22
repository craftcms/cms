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
}
