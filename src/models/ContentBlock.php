<?php
namespace Blocks;

/**
 *
 */
class ContentBlock extends BaseModel
{
	public $required;
	public $content;

	protected $tableName = 'contentblocks';
	protected $_blockType;

	/**
	 * @return array
	 */
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

	protected $hasMany = array(
		'settings' => array('model' => 'ContentBlockSetting', 'foreignKey' => 'block')
	);

	/**
	 * Returns the content block's block type, filled up with settings
	 */
	public function getBlockType()
	{
		if (!isset($this->_blockType))
		{
			if ($this->class)
			{
				$this->_blockType = Blocks::app()->contentBlocks->getBlockType($this->class);
				$this->_blockType->settings = ArrayHelper::expandSettingsArray($this->settings);
			}
			else
				$this->_blockType = false;
		}

		return $this->_blockType;
	}

	/**
	 * Set the block type
	 *
	 * @param $blockType
	 */
	public function setBlockType($blockType)
	{
		$this->_blockType = $blockType;
	}

	/**
	 * Show the block type field
	 * @return string
	 */
	public function field()
	{
		if ($this->blockType)
			return $this->blockType->displayField($this);
		else
			return '';
	}
}
