<?php
namespace Blocks;

/**
 * Block base class
 */
abstract class BaseBlock extends BaseComponent
{
	public $required;

	protected $componentType = 'Block';
	protected $settingsTemplate;

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function getColumnType()
	{
		return PropertyType::Text;
	}

	/**
	 * Display the blocktype's settings.
	 *
	 * @param array $settings
	 * @return string
	 */
	public function displaySettings($settings = array())
	{
		if (empty($this->settingsTemplate))
			return;

		$this->setSettings($settings);

		$variables = array(
			'settings' => $this->getSettings()
		);

		$template = TemplateHelper::render($this->settingsTemplate, $variables);
		return $template;
	}

	/**
	 * Display the field.
	 *
	 * @param $data
	 * @return string
	 */
	public function displayField($data)
	{
		if (empty($this->fieldTemplate))
			return '';

		$variables = array(
			'handle'   => $this->handle,
			'settings' => $this->settings,
			'data'     => $data
		);

		$template = TemplateHelper::render($this->fieldTemplate, $variables);
		return $template;
	}

	/**
	 * Provides an opportunity to modify the post data before it gets saved to the database.
	 * This function is required for blocktypes that post array data that can't be converted to a string.
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function modifyPostData($data)
	{
		return (string)$data;
	}
}
