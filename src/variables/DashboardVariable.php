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
	public function allWidgets()
	{
		$widgets = blx()->dashboard->getAllWidgets();
		return VariableHelper::populateVariables($widgets, 'WidgetVariable');
	}

	/**
	 * Returns the user's widgets.
	 *
	 * @return array
	 */
	public function userWidgets()
	{
		$widgets = blx()->dashboard->getUserWidgets();
		return VariableHelper::populateVariables($widgets, 'WidgetVariable');
	}

	/**
	 * Returns a widget by its class.
	 *
	 * @param string $class
	 * @return BaseWidget
	 */
	public function getWidgetByClass($class)
	{
		$widget = blx()->dashboard->getWidgetByClass($class);
		if ($widget)
			return new WidgetVariable($widget);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return array
	 */
	public function getUserWidgetById($id)
	{
		$widget = blx()->dashboard->getUserWidgetById($id);
		if ($widget)
			return new WidgetVariable($widget);
	}

	/**
	 * Returns the user's widget IDs.
	 *
	 * @return array
	 */
	public function userWidgetIds()
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
