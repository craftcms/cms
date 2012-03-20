<?php
namespace Blocks;

/**
 *
 */
class Block extends Model
{
	// Model properties

	protected $tableName = 'blocks';
	protected $settingsTableName = 'blocksettings';
	protected $foreignKeyName = 'block_id';
	protected $classSuffix = 'Block';
	protected $hasSettings = true;

	protected $attributes = array(
		'name'         => AttributeType::Name,
		'handle'       => AttributeType::Handle,
		'class'        => AttributeType::ClassName,
		'instructions' => AttributeType::Text
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('name','site_id'), 'unique' => true),
		array('columns' => array('handle','site_id'), 'unique' => true)
	);

	// Block subclass properties

	public $blocktypeName;

	public $required;
	public $data;

	protected $settingsTemplate;
	protected $columnType = AttributeType::Text;


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
		return TemplateHelper::namespaceInputs($template, $this->getClassHandle());
	}

	/**
	 * Display the field
	 * @return string
	 */
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

	/**
	 * String representation of the block
	 */
	public function __toString()
	{
		return (string)$this->data;
	}

}
