<?php

class CpDashboardWidgetTag extends Tag
{
	private $_widget;

	public function __construct($widget)
	{
		$this->_widget = $widget;
	}

	public function classname()
	{
		return new StringTag($this->_widget->classname);
	}

	public function title()
	{
		return new StringTag($this->_widget->title);
	}

	public function body()
	{
		return new StringTag($this->_widget->body);
	}
}
