<?php
namespace Blocks;

/**
 *
 */
class WidgetVariable extends BaseModelVariable
{
	/**
	 * Returns a widget type variable based on this widget model.
	 *
	 * @return WidgetTypeVariable|null
	 */
	public function widgetType()
	{
		$widgetType = blx()->dashboard->populateWidgetType($this->model);
		if ($widgetType)
		{
			return new WidgetTypeVariable($widgetType);
		}
	}
}
