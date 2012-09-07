<?php
namespace Blocks;

/**
 *
 */
class EntryRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entries';
	}

	public function defineAttributes()
	{
		return array(
			'slug'          => AttributeType::Slug,
			'uri'           => array(AttributeType::String, 'maxLength' => 150, 'unique' => true),
			'publishDate'   => AttributeType::DateTime,
			/* BLOCKSPRO ONLY */
			'expiryDate'    => AttributeType::DateTime,
			'sortOrder'     => array(AttributeType::Number, 'unsigned' => true),
			'latestDraft'   => array(AttributeType::Number, 'unsigned' => true),
			'latestVersion' => array(AttributeType::Number, 'unsigned' => true),
			/* end BLOCKSPRO ONLY */
			'archived'      => AttributeType::Bool,
		);
	}

	/* BLOCKSPRO ONLY */

	public function defineRelations()
	{
		return array(
			'parent'   => array(static::BELONGS_TO, 'EntryRecord'),
			'section'  => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
			'author'   => array(static::BELONGS_TO, 'UserRecord', 'required' => true),
			'versions' => array(static::HAS_MANY, 'EntryVersionRecord', 'entryId'),
			'children' => array(static::HAS_MANY, 'EntryRecord', 'parentId'),
		);
	}

	/* end BLOCKSPRO ONLY */

	public function defineIndexes()
	{
		return array(
			/* BLOCKS ONLY */
			array('columns' => array('slug'), 'unique' => true),
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			array('columns' => array('slug','sectionId','parentId'), 'unique' => true),
			/* end BLOCKSPRO ONLY */
		);
	}

	protected $_draft;

	/**
	 * Returns the status of the entry
	 *
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
	 *
	 * @return bool
	 */
	public function getLive()
	{
		return ($this->getPublished() && !$this->getPending() && !$this->getExpired());
	}

	/**
	 * Returns whether the entry has been published
	 *
	 * @return bool
	 */
	public function getPublished()
	{
		return (bool)$this->latest_version;
	}

	/**
	 * Returns whether the entry is pending
	 *
	 * @return bool
	 */
	public function getPending()
	{
		return ($this->getPublished() && $this->publish_date && $this->publish_date > DateTimeHelper::currentTime());
	}

	/**
	 * Returns whether the entry has expired
	 *
	 * @return bool
	 */
	public function getExpired()
	{
		return ($this->getPublished() && $this->expiry_date && $this->expiry_date < DateTimeHelper::currentTime());
	}

	/**
	 * Returns the publish date
	 *
	 * @return DateTime
	 */
	public function getPublishDate()
	{
		if ($this->publish_date)
		{
			return $this->publish_date;
		}
		else
			return null;
	}

	/**
	 * Returns the entry's full URL
	 *
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
	 *
	 * @return array
	 */
	public function getDrafts()
	{
		if (!$this->isNewRecord())
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
	 *
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
