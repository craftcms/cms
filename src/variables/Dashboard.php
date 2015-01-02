<?php
namespace craft\app\variables;

use craft\app\models\Widget as WidgetModel;

/**
 * Dashboard functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     3.0
 */
class Dashboard
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
		return WidgetType::populateVariables($widgetTypes);
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 *
	 * @return WidgetType|null
	 */
	public function getWidgetType($class)
	{
		$widgetType = craft()->dashboard->getWidgetType($class);

		if ($widgetType)
		{
			return new WidgetType($widgetType);
		}
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return WidgetType|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		$widgetType = craft()->dashboard->populateWidgetType($widget);
		if ($widgetType)
		{
			return new WidgetType($widgetType);
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
