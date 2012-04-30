<?php
namespace Blocks;

/**
 * Dashboard functions
 */
class DashboardVariable
{
	/**
	 * Returns dashboard alerts
	 * @return array
	 */
	public function alerts()
	{
		return DashboardHelper::getAlerts();
	}

	/**
	 * Returns the user's widgets
	 * @return array
	 */
	public function widgets()
	{
		return b()->dashboard->getUserWidgets();
	}
}
