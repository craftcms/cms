<?php

/**
 *
 */
class CpDashboardWidgetTag extends Tag
{
	private $_widget;

	/**
	 * @access protected
	 *
	 * @param $widget
	 */
	protected function init($widget)
	{
		$this->_widget = $widget;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function title()
	{
		return $this->_widget->title;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function id()
	{
		return $this->_widget->id;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function classname()
	{
		return $this->_widget->className;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function body()
	{
		return $this->_widget->displayBody();
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function settings()
	{
		return $this->_widget->displaySettings();
	}
}
