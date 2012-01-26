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
		echo bJson::encode(array('alerts' => $alerts));
	}
}
