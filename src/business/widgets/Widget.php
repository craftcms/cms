<?php

abstract class Widget
{
	public $settings = array();
	public $title = '';
	public $className = '';
	public $body = '';

	public function __construct($settings = array())
	{
		// merge in the saved settings
		$this->settings = array_merge($this->settings, $settings);

		// call init() so widgets have an easy way to do constructor stuff without having to overwrite __construct
		$this->init();
	}

	protected function init() {}
}
