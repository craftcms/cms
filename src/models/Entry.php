<?php
namespace Blocks;

/**
 *
 */
class Entry extends BaseModel
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
		if ($this->getLive())
			return 'live';
		else if ($this->getPending())
			return 'pending';
		else if ($this->getExpired())
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
		return ($this->getPublished() && !$this->getPending() && !$this->getExpired());
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
		return ($this->getPublished() && $this->publish_date && $this->publish_date > DateTimeHelper::currentTime());
	}

	/**
	 * Returns whether the entry has expired
	 * @return bool
	 */
	public function getExpired()
	{
		return ($this->getPublished() && $this->expiry_date && $this->expiry_date < DateTimeHelper::currentTime());
	}

	/**
	 * Returns the publish date
	 * @return DateTime
	 */
	public function getPublishDate()
	{
		if ($this->publish_date)
		{
			$dt = new DateTime('@'.$this->publish_date);
			return $dt;
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
		if ($this->uri)
		{
			$url = Blocks::getSiteUrl().$this->uri;
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
		if (!$this->getIsNewRecord())
			return blx()->content->getEntryDrafts($this->id);
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
			$draft = blx()->content->getDraftByNum($this->id, $draft);

		if (!$draft)
			return;

		// Keep a reference of the draft for getDraft()
		$this->_draft = $draft;

		// Apply any content changes
		$changes = $draft->getChanges();
		$this->getContent()->setValues($changes);
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getContent()->getValue('title');
	}

	/**
	 * @return mixed
	 */
	public function getBlocks()
	{
		return $this->section->getBlocks();
	}
}
