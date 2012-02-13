<?php
namespace Blocks;

abstract class BaseBlock extends BaseComponent
{
	public $name;
	public $settings = array();

	protected $settingsTemplate;
	protected $classSuffix = 'Block';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Init
	 */
	public function init()
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
			'settings' => $this->settings
		);

		$template = Blocks::app()->controller->loadTemplate($this->settingsTemplate, $tags, true);
		return TemplateHelper::namespaceInputs($template, $this->class);
	}
}
