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
		$widgetTypes = Craft::$app->dashboard->getAllWidgetTypes();
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
		$widgetType = Craft::$app->dashboard->getWidgetType($class);

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
		$widgetType = Craft::$app->dashboard->populateWidgetType($widget);
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
		return Craft::$app->dashboard->getUserWidgets();
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
		return Craft::$app->dashboard->getUserWidgetById($id);
	}

	/**
	 * Returns the user's widget IDs.
	 *
	 * @return array
	 */
	public function userWidgetIds()
	{
		$widgetIds = [];
		$widgets = Craft::$app->dashboard->getUserWidgets();

		foreach ($widgets as $widget)
		{
			$widgetIds[] = $widget->id;
		}

		return $widgetIds;
	}
}
