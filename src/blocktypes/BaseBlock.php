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
	protected $columnType = PropertyType::Text;

	/**
	 * Get the content column type.
	 *
	 * @return string
	 */
	public function getColumnType()
	{
		return $this->columnType;
	}

	/**
	 * Returns the default block settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getDefaultSettings()
	{
		return array();
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

		if (!is_array($settings))
			$settings = array();
		$settings = array_merge($this->getDefaultSettings(), $settings);

		$variables = array(
			'settings' => $settings
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
	 * @param mixed $data
	 * @return string
	 */
	public function modifyPostData($data)
	{
		return (string)$data;
	}
}
