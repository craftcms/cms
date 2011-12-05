<?php

class CpDashboardWidgetTag extends Tag
{
	private $_widget;

	protected function init($widget)
	{
		$this->_widget = $widget;
	}

	public function title()
	{
		return $this->_widget->title;
	}

	public function id()
	{
		return $this->_widget->id;
	}

	public function classname()
	{
		return $this->_widget->className;
	}

	public function body()
	{
		return $this->_widget->displayBody();
	}

	public function settings()
	{
		return $this->_widget->displaySettings();
	}
}