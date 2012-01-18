<?php

/**
 * @abstract
 */
abstract class Widget
{
	public $id;
	public $settings = array();
	public $title = '';
	public $className = '';

	/**
	 * @param $id
	 */
	public function __construct($id)
	{
		$this->id = $id;

		$settings = UserWidgetSettings::model()->findAllByAttributes(array('widget_id' => $this->id));
		$this->settings = array_merge($this->settings, bArrayHelper::expandSettingsArray($settings));

		// call init() so widgets have an easy way to do constructor stuff without having to overwrite __construct
		$this->init();
	}

	/**
	 * @access protected
	 */
	protected function init() {}

	/**
	 * @return bool
	 */
	public function displayBody()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function displaySettings()
	{
		return false;
	}
}
