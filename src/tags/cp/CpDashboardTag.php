<?php
namespace Blocks;

/**
 *
 */
class CpDashboardTag extends Tag
{
	/**
	 * @return array
	 */
	public function alerts()
	{
		return DashboardHelper::getAlerts();
	}

	/**
	 * @return array
	 */
	public function widgets()
	{
		$widgets = Blocks::app()->cp->getDashboardWidgets();
		$tags = array();

		foreach ($widgets as $widget)
		{
			$tags[] = new CpDashboardWidgetTag($widget);
		}

		return $tags;
	}
}
