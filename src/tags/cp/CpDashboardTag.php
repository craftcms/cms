<?php

/**
 *
 */
class CpDashboardTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return array
	 */
	public function alerts()
	{
		return DashboardHelper::getAlerts();
	}

	/**
	 * @access public
	 *
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
