<?php
namespace Blocks;

/**
 *
 */
class ContentBlock extends BaseModel
{
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
		array('columns' => array('site_id','handle'), 'unique' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'ContentBlockSetting', 'foreignKey' => 'block')
	);

	/**
	 * Returns 
	 */
	public function getBlockType()
	{
		if (!isset($this->_blockType))
		{
			if ($this->class)
			{
				$this->_blockType = Blocks::app()->contentBlocks->getBlockType($this->class);

				// Set the block type settings
				$settings = ArrayHelper::expandSettingsArray($this->settings);
				$this->_blockType->setSettings($settings);
			}
			else
				$this->_blockType = false;
		}

		return $this->_blockType;
	}
}
