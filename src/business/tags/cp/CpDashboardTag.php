<?php

class CpDashboardTag extends Tag
{
	public function widgets()
	{
		return Blocks::app()->cp->getDashboardWidgetData();
	}
}
