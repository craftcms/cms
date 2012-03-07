<?php
namespace Blocks;

/**
 * Blocktype Base class
 */
abstract class BaseBlocktype extends Block
{
	public $blocktypeName;

	public $required;
	public $content;

	protected $settingsTemplate;
	protected $columnType = AttributeType::Text;

	protected $classSuffix = 'Blocktype';

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

		$template = b()->controller->loadTemplate($this->settingsTemplate, $tags, true);
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

	public function displayField()
	{
		if (empty($this->fieldTemplate))
			return '';

		$tags = array(
			'block' => $this
		);

		$template = b()->controller->loadTemplate($this->fieldTemplate, $tags, true);
		return $template;
	}
}
