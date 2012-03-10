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
		'slug'        => array('type' => AttributeType::Char, 'maxLength' => 100),
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
	 * Returns the entry's title
	 */
	public function getTitle()
	{
		return $this->content->title;
	}

	/**
	 * Adds content block handles to the mix of possible magic getter properties
	 */
	public function __get($name)
	{
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Maybe it's a block?
			if (isset($this->blocks[$name]))
			{
				return $this->blocks[$name];
			}
			throw $e;
		}
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
