<?php

/**
 *
 */
class bDashboardController extends bBaseController
{
	/**
	 */
	public function actionGetAlerts()
	{
		$alerts = bDashboardHelper::getAlerts(true);
		echo CJSON::encode(array('alerts' => $alerts));
	}
}
