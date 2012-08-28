<?php
namespace Blocks;

/**
 *
 */
class Entry extends BaseModel
{
	public function getTableName()
	{
		return 'entries';
	}

	protected function getProperties()
	{
		return array(
			'slug'           => array(PropertyType::Char, 'maxLength' => 50),
			'uri'            => array(PropertyType::Varchar, 'maxLength' => 150, 'unique' => true),
			'publish_date'   => PropertyType::Int,
			'expiry_date'    => PropertyType::Int,
			'sort_order'     => array(PropertyType::Int, 'unsigned' => true),
			'latest_draft'   => PropertyType::Int,
			'latest_version' => PropertyType::Int,
			'archived'       => PropertyType::Boolean,
		);
	}

	protected function getRelations()
	{
		return array(
			'parent'   => array(static::BELONGS_TO, 'Entry'),
			'section'  => array(static::BELONGS_TO, 'Section', 'required' => true),
			'author'   => array(static::BELONGS_TO, 'User', 'required' => true),
			'versions' => array(static::HAS_MANY, 'EntryVersion', 'entry_id'),
			'children' => array(static::HAS_MANY, 'Entry', 'parent_id'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('slug','section_id','parent_id'), 'unique' => true),
		);
	}

	protected $_draft;

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
