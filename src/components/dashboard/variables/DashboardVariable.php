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
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 * @return WidgetTypeVariable|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		$widgetType = blx()->dashboard->populateWidgetType($widget);
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
		return blx()->dashboard->getUserWidgets();
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return WidgetModel|null
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
