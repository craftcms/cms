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
	 * Returns all installed widget types.
	 *
	 * @return array
	 */
	public function getAllWidgetTypes()
	{
		$widgetTypes = blx()->dashboard->getAllWidgetTypes();
		return WidgetTypeVariable::populateVariables($widgetTypes);
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 * @return WidgetTypeVariable|null
	 */
	public function getWidgetType($class)
	{
		$widgetType = blx()->dashboard->getWidgetType($class);
		if ($widgetType)
		{
			return new WidgetTypeVariable($widgetType);
		}
	}

	/**
	 * Returns the user's widgets.
	 *
	 * @return array
	 */
	public function userWidgets()
	{
		$widgets = blx()->dashboard->getUserWidgets();
		return WidgetVariable::populateVariables($widgets);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return WidgetVariable|null
	 */
	public function getUserWidgetById($id)
	{
		$widget = blx()->dashboard->getUserWidgetById($id);
		if ($widget)
		{
			return new WidgetVariable($widget);
		}
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
			$widgetIds[] = $widget->id;
		}

		return $widgetIds;
	}
}
