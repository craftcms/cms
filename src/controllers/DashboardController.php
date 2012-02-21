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
	public function run()
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
