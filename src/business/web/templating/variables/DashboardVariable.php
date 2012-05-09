<?php
namespace Blocks;

/**
 * Dashboard functions
 */
class DashboardVariable
{
	/**
	 * Returns dashboard alerts.
	 * @return array
	 */
	public function alerts()
	{
		return DashboardHelper::getAlerts();
	}

	/**
	 * Returns all installed widgets.
	 * @return array
	 */
	public function allwidgets()
	{
		$widgets = b()->dashboard->getAllWidgets();
		return $widgets;
	}

	/**
	 * Returns the user's widgets.
	 * @return array
	 */
	public function userwidgets()
	{
		$widgets = b()->dashboard->getUserWidgets();
		return $widgets;
	}
}
