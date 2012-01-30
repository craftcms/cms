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
		$alerts = bDashboardHelper::getAlerts(true);
		echo bJson::encode(array('alerts' => $alerts));
	}
}
