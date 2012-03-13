<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends Controller
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
		$r = array('alerts' => $alerts);
		$this->returnJson($r);
	}
}
