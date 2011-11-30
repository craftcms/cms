<?php

class CpDashboardWidgetTag extends Tag
{
	private $_widget;

	public function __construct($widget)
	{
		$this->_widget = $widget;
	}

	public function className()
	{
		return new StringTag($this->_widget->className);
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
