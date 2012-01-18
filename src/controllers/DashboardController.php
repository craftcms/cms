<?php

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 */
	public function actionGetAlerts()
	{
		$alerts = bDashboardHelper::getAlerts(true);
		echo CJSON::encode(array('alerts' => $alerts));
	}
}
