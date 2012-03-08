<?php
namespace Blocks;

/**
 *
 */
class Entry extends BaseModel
{
	protected $tableName = 'entries';
	protected $hasContent = true;

	protected $attributes = array(
		'slug'        => AttributeType::Handle,
		'full_uri'    => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'unique' => true),
		'post_date'   => AttributeType::Int,
		'expiry_date' => AttributeType::Int,
		'sort_order'  => array('type' => AttributeType::Int, 'unsigned' => true),
		'enabled'     => array('type' => AttributeType::Boolean, 'default' => true),
		'archived'    => AttributeType::Boolean
	);

	protected $belongsTo = array(
		'parent'  => array('model' => 'Entry'),
		'section' => array('model' => 'Section', 'required' => true),
		'author'  => array('model' => 'User', 'required' => true)
	);

	protected $hasMany = array(
		'children' => array('model' => 'Entry', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('section_id','slug'), 'unique' => true),
	);

	/**
	 * @return string
	 */
	public function title()
	{
		if ($this->content->title)
			return $this->content->title;
		else
			return 'Untitled';
	}

	/**
	 * @return mixed
	 */
	public function getBlocks()
	{
		if (!isset($this->_blocks))
		{
			$content = $this->content;
			$blocks = $this->section->blocks;

			foreach ($blocks as $block)
			{
				$colName = b()->blocks->getContentColumnNameForBlock($block);
				$block->data = $content->$colName;
			}

			$this->_blocks = $blocks;
		}
		
		return $this->_blocks;
	}
}
