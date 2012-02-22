<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 * All dashboard actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 */
	public function actionGetAlerts()
	{
		$alerts = DashboardHelper::getAlerts(true);
		echo Json::encode(array('alerts' => $alerts));
	}
}
