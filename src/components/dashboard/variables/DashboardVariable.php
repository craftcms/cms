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
		return blx()->dashboard->getUserWidgets();
	}

	/**
	 * Returns a widget by its class.
	 *
	 * @param string $class
	 * @return BaseWidget|null
	 */
	public function getWidgetByClass($class)
	{
		$widget = blx()->dashboard->getWidgetByClass($class);
		if ($widget)
		{
			return new WidgetVariable($widget);
		}
	}

	/**
	 * Populates a widget.
	 *
	 * @param WidgetPackage $widgetPackage
	 * @return BaseWidget|null
	 */
	public function populateWidget(WidgetPackage $widgetPackage)
	{
		$widget = blx()->dashboard->populateWidget($widgetPackage);
		if ($widget)
		{
			return new WidgetVariable($widget);
		}
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return WidgetPackage|null
	 */
	public function getUserWidgetById($id)
	{
		return blx()->dashboard->getUserWidgetById($id);
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
