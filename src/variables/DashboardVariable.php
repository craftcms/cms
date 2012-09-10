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
	public function getAllWidgets()
	{
		return blx()->dashboard->getAllWidgets();
	}

	/**
	 * Returns a widget by its class.
	 *
	 * @param string $class
	 * @return BaseWidget
	 */
	public function getWidgetByClass($class)
	{
		return blx()->dashboard->getWidgetByClass($class);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getWidgetById($id)
	{
		return blx()->dashboard->getWidgetById($id);
	}

	/**
	 * Returns the user's widgets.
	 *
	 * @return array
	 */
	public function userWidgets()
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
