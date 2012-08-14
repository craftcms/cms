<?php
namespace Blocks;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseComponent
{
	public $name;
	public $title = '';

	protected $componentType = 'Widget';
	protected $bodyTemplate;
	protected $settingsTemplate;
	protected $settings = array();

	/**
	 *
	 */
	public function init()
	{
		if (isset($this->record))
		{
			$this->settings = array_merge($this->settings, $this->record->getSettings());
		}

		parent::init();
	}

	/**
	 * Adds action buttons to the widget.
	 * @return array
	 */
	public function getActionButtons()
	{
		return array();
	}

	/**
	 * Display the widget's body
	 * @return bool
	 */
	public function displayBody()
	{
		if (empty($this->bodyTemplate))
			return '';

		$variables = array(
			'widget' => $this
		);

		$template = TemplateHelper::render($this->bodyTemplate, $variables);
		return $template;
	}

	/**
	 * Display the settings form
	 * @param $idPrefix
	 * @param $namePrefix
	 * @return bool
	 */
	public function displaySettings($idPrefix, $namePrefix)
	{
		if (empty($this->settingsTemplate))
			return '';

		$variables = array(
			'idPrefix'   => $idPrefix,
			'namePrefix' => $namePrefix,
			'settings'   => $this->settings
		);

		$template = TemplateHelper::render($this->settingsTemplate, $variables);
		return $template;
	}
}
