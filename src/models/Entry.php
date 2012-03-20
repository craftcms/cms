<?php
namespace Blocks;

/**
 *
 */
class Entry extends Model
{
	public $draft;

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
		'drafts'   => array('model' => 'Draft', 'foreignKey' => 'entry'),
		'children' => array('model' => 'Entry', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('section_id','slug'), 'unique' => true),
	);

	/**
	 * Returns whether the entry is published
	 */
	public function getPublished()
	{
		return !$this->content->isNewRecord;
	}

	/**
	 * Returns the entry's title
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->content->title;
	}

	/**
	 * Returns the entry's URI
	 * @return mixed
	 */
	public function getUri()
	{
		if ($this->slug)
		{
			$urlFormat = $this->section->url_format;
			$uri = str_replace('{slug}', $this->slug, $urlFormat);
			return $uri;
		}
		else
			return null;
	}

	/**
	 * Returns the entry's full URL
	 * @return mixed
	 */
	public function getUrl()
	{
		$uri = $this->uri;
		if ($uri)
		{
			$url = b()->sites->currentSite->url.'/'.$uri;
			$url = str_replace('http://', '', $url);
			return $url;
		}
		else
			return null;
	}

	/**
	 * Mix-in draft content
	 * @param Draft $draft
	 */
	public function mixInDraftContent($draft)
	{
		// Index draft content by block ID
		$draftContent = $draft->content;
		$draftContentByBlockId = array();
		foreach ($draftContent as $block)
		{
			$draftContentByBlockId[$block->block_id] = $block->value;
		}

		// Save draft data onto blocks
		foreach ($this->blocks as $block)
		{
			if (isset($draftContentByBlockId[$block->id]))
				$block->data = $draftContentByBlockId[$block->id];
		}
	}

	/**
	 * Adds content block handles to the mix of possible magic getter properties
	 *
	 * @param $name
	 * @return mixed
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
