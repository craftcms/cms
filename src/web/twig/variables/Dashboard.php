<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\base\Widget;
use craft\app\base\WidgetInterface;

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
	 * Creates a widget with a given config.
	 *
	 * @param mixed $config The widget’s class name, or its config, with a `type` value and optionally a `settings` value
	 * @return WidgetInterface|Widget The widget
	 */
	public function createWidget($config)
	{
		return \Craft::$app->getDashboard()->createWidget($config);
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @param string|null $indexBy The attribute to index the widgets by
	 * @return WidgetInterface[]|Widget[] The widgets
	 */
	public function getAllWidgets($indexBy = null)
	{
		return \Craft::$app->getDashboard()->getAllWidgets($indexBy);
	}

	/**
	 * Returns all available widget type classes.
	 *
	 * @return WidgetInterface[] The available widget type classes.
	 */
	public function getAllWidgetTypes()
	{
		return \Craft::$app->getDashboard()->getAllWidgetTypes();
	}

	/**
	 * Returns info about a widget type.
	 *
	 * @param string|WidgetInterface|Widget $widget A widget or widget type
	 * @return ComponentInfo Info about the widget type
	 */
	public function getWidgetTypeInfo($widget)
	{
		return new ComponentInfo($widget);
	}
}
