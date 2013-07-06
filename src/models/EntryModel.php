<?php
namespace Craft;

/**
 * Entry model class
 */
class EntryModel extends BaseElementModel
{
	protected $elementType = ElementType::Entry;

	private $_tags;

	const LIVE     = 'live';
	const PENDING  = 'pending';
	const EXPIRED  = 'expired';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'sectionId'  => AttributeType::Number,
			'authorId'   => AttributeType::Number,
			'slug'       => AttributeType::String,
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
		));
	}

	/**
	 * Returns the entry's section.
	 *
	 * @return SectionModel|null
	 */
	public function getSection()
	{
		if ($this->sectionId)
		{
			return craft()->sections->getSectionById($this->sectionId);
		}
	}

	/**
	 * Returns the entry's author.
	 *
	 * @return UserModel|null
	 */
	public function getAuthor()
	{
		if ($this->authorId)
		{
			return craft()->users->getUserById($this->authorId);
		}
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		if ($status == static::ENABLED && $this->postDate)
		{
			$currentTime = DateTimeHelper::currentTimeStamp();
			$postDate    = $this->postDate->getTimestamp();
			$expiryDate  = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

			if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
			{
				return static::LIVE;
			}
			else if ($postDate > $currentTime)
			{
				return static::PENDING;
			}
			else
			{
				return static::EXPIRED;
			}
		}

		return $status;
	}

	/**
	 * Returns the entry's tags.
	 *
	 * @return array
	 */
	public function getTags()
	{
		if (!isset($this->_tags))
		{
			if ($this->id)
			{
				$this->_tags = craft()->entries->getTagsByEntryId($this->id);
			}
			else
			{
				$this->_tags = array();
			}
		}

		return $this->_tags;
	}

	/**
	 * Sets the entry's tags.
	 *
	 * @param array|string $tags
	 */
	public function setTags($tags)
	{
		$this->_tags = ArrayHelper::stringToArray($tags);
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('entries/'.$this->getSection()->handle.'/'.$this->id);
	}
}
