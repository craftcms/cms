<?php
namespace Blocks;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseComponent
{
	protected $componentType = 'Widget';
	protected $bodyTemplate;
	protected $settingsTemplate;
	protected $settings = array();

	/**
	 * Gets the widget title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		// Default to the widget name
		return $this->getName();
	}

	/**
	 * Adds action buttons to the widget.
	 *
	 * @return array
	 */
	public function getActionButtons()
	{
		return array();
	}

	/**
	 * Gets the widget body.
	 *
	 * @return string
	 */
	public function getBody()
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
	 *
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
