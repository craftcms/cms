<?php
namespace Craft;

/**
 *
 */
class CpController extends BaseController
{
	/**
	 * Loads any CP alerts.
	 */
	public function actionGetAlerts()
	{
		$this->requireAjaxRequest();

		// Fetch 'em and send 'em
		$alerts = CpHelper::getAlerts(true);
		$this->returnJson($alerts);
	}
}
