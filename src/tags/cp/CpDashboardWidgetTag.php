<?php

/**
 *
 */
class CpDashboardWidgetTag extends Tag
{
	private $_widget;

	/**
	 * @access protected
	 * @param $widget
	 */
	protected function init($widget)
	{
		$this->_widget = $widget;
	}

	/**
	 * @return mixed
	 */
	public function title()
	{
		return $this->_widget->title;
	}

	/**
	 * @return mixed
	 */
	public function id()
	{
		return $this->_widget->id;
	}

	/**
	 * @return mixed
	 */
	public function classname()
	{
		return $this->_widget->className;
	}

	/**
	 * @return mixed
	 */
	public function body()
	{
		return $this->_widget->displayBody();
	}

	/**
	 * @return mixed
	 */
	public function settings()
	{
		return $this->_widget->displaySettings();
	}
}
