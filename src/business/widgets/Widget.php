<?php

abstract class Widget
{
	public $id;
	public $settings = array();
	public $title = '';
	public $className = '';

	public function __construct($id)
	{
		$this->id = $id;

		$settings = UserWidgetSettings::model()->findAllByAttributes(array('widget_id' => $this->id));
		$this->settings = array_merge($this->settings, ArrayHelper::expandSettingsArray($settings));

		// call init() so widgets have an easy way to do constructor stuff without having to overwrite __construct
		$this->init();
	}

	protected function init() {}

	public function displayBody()
	{
		return false;
	}

	public function displaySettings()
	{
		return false;
	}
}
