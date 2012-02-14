<?php
namespace Blocks;

abstract class BaseBlock extends BaseComponent
{
	public $name;

	protected $settings = array();
	protected $settingsTemplate;
	protected $classSuffix = 'Block';

	public function validateSettings($settings = array())
	{
		return true;
	}

	public function onBeforeSaveSettings($settings = array())
	{
		return $settings;
	}

	public function setSettings($settings)
	{
		$this->settings = array_merge($this->settings, $settings);
	}

	/**
	 * Display settings
	 */
	public function displaySettings()
	{
		if (empty($this->settingsTemplate))
			return '';

		$tags = array(
			'settings' => $this->settings
		);

		$template = Blocks::app()->controller->loadTemplate($this->settingsTemplate, $tags, true);
		return TemplateHelper::namespaceInputs($template, $this->class);
	}
}
