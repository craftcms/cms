<?php
namespace Blocks;

abstract class BaseBlock extends BaseComponent
{
	public $name;
	public $errors = array();

	protected $settings = array();
	protected $settingsTemplate;
	protected $classSuffix = 'Block';

	public function setSettings($settings)
	{
		if (is_array($settings))
			$this->settings = array_merge($this->settings, $settings);
	}

	public function getSettings()
	{
		return $this->settings;
	}

	public function validateSettings()
	{
		return true;
	}

	public function onBeforeSaveSettings()
	{
	}

	/**
	 * Display settings
	 */
	public function displaySettings()
	{
		if (empty($this->settingsTemplate))
			return '';

		$tags = array(
			'settings' => $this->settings,
			'errors'   => $this->errors
		);

		$template = Blocks::app()->controller->loadTemplate($this->settingsTemplate, $tags, true);
		return TemplateHelper::namespaceInputs($template, $this->class);
	}
}
