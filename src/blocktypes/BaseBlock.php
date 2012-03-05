<?php
namespace Blocks;

abstract class BaseBlock extends BaseComponent
{
	public $name;

	protected $settings = array();
	protected $settingsTemplate;
	protected $columnType = AttributeType::Text;

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

	public function onBeforeSaveSettings()
	{
	}

	/**
	 * Display settings
	 * @return string
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

	/**
	 * Get the content column type
	 * @return string
	 */
	public function getColumnType()
	{
		return $this->columnType;
	}

	public function displayField($block)
	{
		if (empty($this->fieldTemplate))
			return '';

		$tags = array(
			'block'    => $block,
			'settings' => $this->settings
		);

		$template = Blocks::app()->controller->loadTemplate($this->fieldTemplate, $tags, true);
		return $template;
	}
}
