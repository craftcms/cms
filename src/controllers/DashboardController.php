<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 */
	public function actionGetAlerts()
	{
		$alerts = DashboardHelper::getAlerts(true);
		echo Json::encode(array('alerts' => $alerts));
	}
}
