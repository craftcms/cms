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
		return VariableHelper::populateVariables($widgetTypes, 'WidgetVariable');
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
	 * Returns a widget type.
	 *
	 * @param string $class
	 * @return BaseWidget|null
	 */
	public function getWidgetType($class)
	{
		$widgetType = blx()->dashboard->getWidgetType($class);
		if ($widgetType)
		{
			return new WidgetVariable($widgetType);
		}
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetPackage $widgetPackage
	 * @return BaseWidget|null
	 */
	public function populateWidgetType(WidgetPackage $widgetPackage)
	{
		$widgetType = blx()->dashboard->populateWidgetType($widgetPackage);
		if ($widgetType)
		{
			return new WidgetVariable($widgetType);
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
