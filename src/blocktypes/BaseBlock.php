<?php
namespace Blocks;

/**
 * Block base class
 */
abstract class BaseBlock extends BaseComponent
{
	public $blocktypeName;
	public $required;

	protected $componentType = 'Block';
	protected $settingsTemplate;
	protected $columnType = PropertyType::Text;

	/**
	 * Getter
	 */
	public function __get($name)
	{
		//if (in_array($name, array('name', 'handle', 'class'))
	}

	/**
	 * Get the content column type
	 * @return string
	 */
	public function getColumnType()
	{
		return $this->columnType;
	}

	/**
	 * Display the blocktype's settings
	 *
	 * @param $idPrefix
	 * @param $namePrefix
	 * @return string
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

	/**
	 * Display the field
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
