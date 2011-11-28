<?php

class CpDashboardTag extends Tag
{
	public function widgets()
	{
		$widgets = Blocks::app()->cp->getDashboardWidgets();
		$tags = array();

		foreach ($widgets as $widget)
		{
			$tags[] = new CpDashboardWidgetTag($widget);
		}

		return new ArrayTag($tags);
	}
}
