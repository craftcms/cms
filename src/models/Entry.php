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
		'slug'           => array('type' => AttributeType::Char, 'maxLength' => 100),
		'full_uri'       => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'unique' => true),
		'post_date'      => AttributeType::Int,
		'expiry_date'    => AttributeType::Int,
		'sort_order'     => array('type' => AttributeType::Int, 'unsigned' => true),
		'latest_draft'   => AttributeType::Int,
		'latest_version' => AttributeType::Int,
		'archived'       => AttributeType::Boolean
	);

	protected $belongsTo = array(
		'parent'  => array('model' => 'Entry'),
		'section' => array('model' => 'Section', 'required' => true),
		'author'  => array('model' => 'User', 'required' => true)
	);

	protected $hasMany = array(
		'versions' => array('model' => 'EntryVersion', 'foreignKey' => 'entry'),
		'children' => array('model' => 'Entry', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('slug','section_id','parent_id'), 'unique' => true),
	);

	/**
	 * Use the section's content table name
	 */
	public function getContentTableName()
	{
		return $this->section->getContentTableName();
	}

	/**
	 * There is no single "entrycontent" table
	 */
	public function createContentTable()
	{
	}

	/**
	 * Returns whether the entry has been published
	 */
	public function getPublished()
	{
		return (bool)$this->latest_version;
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
	 * Get all drafts
	 */
	public function getDrafts()
	{
		if (!$this->isNewRecord)
			return b()->content->getEntryDrafts($this->id);
		else
			return array();
	}

	/**
	 * Mix-in draft content
	 * @param EntryVersion $draft
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

	public function getTitle()
	{
		if (isset($this->content['title']))
			return $this->content['title'];
		else
			return '';
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
				if (isset($content[$block->handle]))
					$block->data = $content[$block->handle];
			}

			$this->_blocks = $blocks;
		}
		
		return $this->_blocks;
	}
}
