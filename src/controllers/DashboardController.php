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
		$alerts = DashboardHelper::getAlerts(true);
		echo CJSON::encode(array('alerts' => $alerts));
	}
}
