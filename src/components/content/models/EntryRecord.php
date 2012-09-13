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
			'author'   => array(static::BELONGS_TO, 'UserRecord', 'required' => true),
			'section'  => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
			'versions' => array(static::HAS_MANY, 'EntryVersionRecord', 'entryId'),
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
			array('columns' => array('slug','sectionId'), 'unique' => true),
			/* end BLOCKSPRO ONLY */
		);
	}

	protected $_draft;

	/**
	 * Returns the entry's status (live, pending, expired, offline).
	 *
	 * @return string
	 */
	public function getStatus()
	{
		if ($this->isLive())
			return 'live';
		else if ($this->isPending())
			return 'pending';
		/* BLOCKSPRO ONLY */
		else if ($this->hasExpired())
			return 'expired';
		/* end BLOCKSPRO ONLY */
		else
			return 'offline';
	}

	/**
	 * Returns whether the entry is live.
	 *
	 * @return bool
	 */
	public function isLive()
	{
		/* BLOCKS ONLY */
		return ($this->getPublished() && !$this->isPending());
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		return ($this->getPublished() && !$this->isPending() && !$this->hasExpired());
		/* end BLOCKSPRO ONLY */
	}

	/**
	 * Returns whether the entry is offline.
	 *
	 * @return bool
	 */
	public function isOffline()
	{
		return !$this->getPublished();
	}

	/**
	 * Returns whether the entry has been published.
	 *
	 * @return bool
	 */
	public function isPublished()
	{
		return (bool)$this->publishDate;
	}

	/**
	 * Returns whether the entry is pending.
	 *
	 * @return bool
	 */
	public function isPending()
	{
		return ($this->getPublished() && $this->publishDate && $this->publishDate > DateTimeHelper::currentTime());
	}

	/* BLOCKSPRO ONLY */
	/**
	 * Returns whether the entry has expired.
	 *
	 * @return bool
	 */
	public function hasExpired()
	{
		return ($this->getPublished() && $this->expiryDate && $this->expiryDate < DateTimeHelper::currentTime());
	}
	/* end BLOCKSPRO ONLY */

	/**
	 * Returns the entry's full URL
	 *
	 * @return mixed
	 */
	public function getUrl()
	{
		if ($this->uri)
		{
			return Blocks::getSiteUrl().$this->uri;
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
