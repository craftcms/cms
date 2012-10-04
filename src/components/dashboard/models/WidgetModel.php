<?php
namespace Blocks;

/**
 * Widget model class
 *
 * Used for transporting widget data throughout the system.
 */
class WidgetModel extends BaseComponentModel
{
	/**
	 * Saves the Widget.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->dashboard->saveUserWidget($this);
	}

	/**
	 * Returns the widget type
	 *
	 * @return BaseWidget|null
	 */
	public function widgetType()
	{
		return blx()->dashboard->populateWidgetType($this);
	}
}
