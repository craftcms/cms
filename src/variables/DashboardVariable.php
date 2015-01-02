<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\Widget as WidgetModel;

/**
 * Dashboard functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DashboardVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all installed widget types.
	 *
	 * @return array
	 */
	public function getAllWidgetTypes()
	{
		$widgetTypes = craft()->dashboard->getAllWidgetTypes();
		return WidgetTypeVariable::populateVariables($widgetTypes);
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 *
	 * @return WidgetTypeVariable|null
	 */
	public function getWidgetType($class)
	{
		$widgetType = craft()->dashboard->getWidgetType($class);

		if ($widgetType)
		{
			return new WidgetTypeVariable($widgetType);
		}
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return WidgetTypeVariable|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		$widgetType = craft()->dashboard->populateWidgetType($widget);
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
	public function getUserWidgets()
	{
		return craft()->dashboard->getUserWidgets();
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 *
	 * @return WidgetModel|null
	 */
	public function getUserWidgetById($id)
	{
		return craft()->dashboard->getUserWidgetById($id);
	}

	/**
	 * Returns the user's widget IDs.
	 *
	 * @return array
	 */
	public function userWidgetIds()
	{
		$widgetIds = array();
		$widgets = craft()->dashboard->getUserWidgets();

		foreach ($widgets as $widget)
		{
			$widgetIds[] = $widget->id;
		}

		return $widgetIds;
	}
}
