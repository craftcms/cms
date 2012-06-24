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

		$template = blx()->controller->loadTemplate($this->bodyTemplate, $variables, true);
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

		$template = blx()->controller->loadTemplate($this->settingsTemplate, $variables, true);
		return $template;
	}
}
