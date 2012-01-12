<?php

/**
 *
 */
class DashboardController extends BaseController
{
	/**
	 * @access public
	 */
	public function actionGetAlerts()
	{
		$alerts = DashboardHelper::getAlerts(true);
		echo CJSON::encode(array('alerts' => $alerts));
	}
}
