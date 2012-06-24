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
		$widgets = blx()->dashboard->getAllWidgets();
		return $widgets;
	}

	/**
	 * Returns the user's widgets.
	 * @return array
	 */
	public function userwidgets()
	{
		$widgets = blx()->dashboard->getUserWidgets();
		return $widgets;
	}

	/**
	 * Returns the user's widget IDs.
	 * @return array
	 */
	public function userwidgetids()
	{
		$widgetIds = array();
		$widgets = blx()->dashboard->getUserWidgets();

		foreach ($widgets as $widget)
		{
			$widgetIds[] = $widget->id;
		}
		return $widgetIds;
	}
}
