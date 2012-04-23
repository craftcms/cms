<?php
namespace Blocks;

/**
 *
 */
class Entry extends Model
{
	protected $tableName = 'entries';
	public $hasContent = true;

	protected $attributes = array(
		'slug'           => array('type' => AttributeType::Char, 'maxLength' => 100),
		'uri'            => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'unique' => true),
		'publish_date'   => AttributeType::Int,
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

	protected $_draft;

	/**
	 * Use the section's content table name
	 * @return mixed
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
	 * Returns the status of the entry
	 * @return string The entry status (live, pending, expired, offline)
	 */
	public function getStatus()
	{
		if ($this->live)
			return 'live';
		else if ($this->pending)
			return 'pending';
		else if ($this->expired)
			return 'expired';
		else
			return 'offline';
	}

	/**
	 * Returns whether the entry is live
	 * @return bool
	 */
	public function getLive()
	{
		return ($this->published && !$this->pending && !$this->expired);
	}

	/**
	 * Returns whether the entry has been published
	 * @return bool
	 */
	public function getPublished()
	{
		return (bool)$this->latest_version;
	}

	/**
	 * Returns whether the entry is pending
	 * @return bool
	 */
	public function getPending()
	{
		return ($this->published && $this->publish_date && $this->publish_date > DateTimeHelper::currentTime());
	}

	/**
	 * Returns whether the entry has expired
	 * @return bool
	 */
	public function getExpired()
	{
		return ($this->published && $this->expiry_date && $this->expiry_date < DateTimeHelper::currentTime());
	}

	/**
	 * Returns the entry's full URL
	 * @return mixed
	 */
	public function getUrl()
	{
		if ($this->uri)
		{
			$url = b()->sites->current->url.'/'.$this->uri;
			$url = str_replace('http://', '', $url);
			return $url;
		}
		else
			return null;
	}

	/**
	 * Get all drafts
	 * @return array
	 */
	public function getDrafts()
	{
		if (!$this->isNewRecord)
			return b()->content->getEntryDrafts($this->id);
		else
			return array();
	}

	/**
	 * Returns the draft
	 */
	public function getDraft()
	{
		return $this->_draft;
	}

	/**
	 * Sets a draft
	 * @param EntryVersion $draft
	 */
	public function setDraft($draft)
	{
		if (is_numeric($draft))
			$draft = b()->content->getDraftByNum($this->id, $draft);

		if (!$draft)
			return;

		$this->_draft = $draft;

		$changes = json_decode($draft->changes, true);

		if (isset($changes['title']))
			$this->title = $changes['title'];

		if (isset($changes['blocks']))
		{
			// Get all of the entry's blocks, indexed by their IDs
			$blocksById = array();
			foreach ($this->blocks as $block)
			{
				$blocksById[$block->id] = $block;
			}

			foreach ($changes['blocks'] as $blockId => $blockData)
			{
				if (isset($blocksById[$blockId]))
					$blocksById[$blockId]->data = $blockData;
			}
		}
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->content->title;
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
				$blockHandle = $block->handle;
				$block->data = $content->$blockHandle;
			}

			$this->_blocks = $blocks;
		}

		return $this->_blocks;
	}
}
