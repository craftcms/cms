<?php
namespace Blocks;

/**
 * Dashboard functions
 */
class DashboardVariable
{
	/**
	 * Returns dashboard alerts.
	 *
	 * @return array
	 */
	public function alerts()
	{
		return DashboardHelper::getAlerts();
	}

	/**
	 * Returns all installed widget components.
	 *
	 * @return array
	 */
	public function allwidgets()
	{
		return blx()->dashboard->getAllWidgets();
	}

	/**
	 * Returns the user's widgets.
	 *
	 * @return array
	 */
	public function userwidgets()
	{
		return blx()->dashboard->getUserWidgets();
	}

	/**
	 * Returns the user's widget IDs.
	 *
	 * @return array
	 */
	public function userwidgetids()
	{
		$widgetIds = array();
		$widgets = blx()->dashboard->getUserWidgets();

		foreach ($widgets as $widget)
		{
			$widgetIds[] = $widget->record->id;
		}
		return $widgetIds;
	}
}
