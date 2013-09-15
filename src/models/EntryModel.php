<?php
namespace Craft;

/**
 * Entry model class
 */
class EntryModel extends BaseElementModel
{
	protected $elementType = ElementType::Entry;

	private $_ancestors;
	private $_descendants;
	private $_parent;

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
			'typeId'     => AttributeType::Number,
			'authorId'   => AttributeType::Number,
			'root'       => AttributeType::Number,
			'lft'        => AttributeType::Number,
			'rgt'        => AttributeType::Number,
			'depth'      => AttributeType::Number,
			'slug'       => AttributeType::String,
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,

			// Just used for saving entries
			'parentId'   => AttributeType::Number,
		));
	}

	/**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
		return $this->getSection()->handle.'/'.$this->slug;
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
	 * Returns the type of entry.
	 *
	 * @return EntryTypeModel|null
	 */
	public function getType()
	{
		$section = $this->getSection();

		if ($section)
		{
			$sectionEntryTypes = $section->getEntryTypes('id');

			if ($sectionEntryTypes)
			{
				if ($this->typeId && isset($sectionEntryTypes[$this->typeId]))
				{
					return $sectionEntryTypes[$this->typeId];
				}
				else
				{
					// Just return the first one
					return $sectionEntryTypes[array_shift(array_keys($sectionEntryTypes))];
				}
			}
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
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		if ($this->getSection())
		{
			return UrlHelper::getCpUrl('entries/'.$this->getSection()->handle.'/'.$this->id);
		}
	}

	/**
	 * Returns the entry's ancestors.
	 *
	 * @param int|null $delta
	 * @return array
	 */
	public function getAncestors($delta = null)
	{
		if (!isset($this->_ancestors))
		{
			if ($this->id)
			{
				$this->_ancestors = craft()->entries->getEntryAncestors($this);
			}
			else
			{
				$this->_ancestors = array();
			}
		}

		if ($delta)
		{
			return array_slice($this->_ancestors, count($this->_ancestors) - $delta);
		}
		else
		{
			return $this->_ancestors;
		}
	}

	/**
	 * Sets the entry's ancestors.
	 *
	 * @param array $ancestors
	 */
	public function setAncestors($ancestors)
	{
		$this->_ancestors = $ancestors;
	}

	/**
	 * Get the entry's parent.
	 *
	 * @return EntryModel|null
	 */
	public function getParent()
	{
		if (!isset($this->_parent))
		{
			$parent = $this->getAncestors(1);

			if ($parent)
			{
				$this->_parent = $parent[0];
			}
			else
			{
				$this->_parent = false;
			}
		}

		if ($this->_parent !== false)
		{
			return $this->_parent;
		}
	}

	/**
	 * Sets the entry's parent.
	 *
	 * @param EntryModel $parent
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;
	}

	/**
	 * Overrides the (deprecated) BaseElementModel::getParents() so it only works for Channel sections, until it's removed altogether.
	 *
	 * @param mixed $field
	 * @return null|ElementCriteriaModel
	 */
	public function getParents($field = null)
	{
		if ($this->getSection()->type == Sectiontype::Channel)
		{
			return parent::getParents($field);
		}
	}

	/**
	 * Returns all of the entry's siblings.
	 *
	 * @return array
	 */
	public function getSiblings()
	{
		$parent = $this->getParent();

		if ($parent)
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->descendantOf($parent);
			$criteria->descendantDelta(1);
			$criteria->id = 'not '.$this->id;
			return $criteria->find();
		}
		else
		{
			return array();
		}
	}

	/**
	 * Returns the entry's previous sibling.
	 *
	 * @return EntryModel|null
	 */
	public function getPrevSibling()
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->prevSiblingOf($this);
		return $criteria->first();
	}

	/**
	 * Returns the entry's next sibling.
	 *
	 * @return EntryModel|null
	 */
	public function getNextSibling()
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->nextSiblingOf($this);
		return $criteria->first();
	}

	/**
	 * Overrides the (deprecated) BaseElementModel::getChildren() so that it returns the actual children for entries within Structure sections.
	 *
	 * @param mixed $field
	 * @return array|ElementCriteriaModel
	 */
	public function getChildren($field = null)
	{
		if ($this->getSection()->type == Sectiontype::Channel)
		{
			return parent::getChildren($field);
		}
		else
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->descendantOf($this);
			$criteria->descendantDelta(1);
			return $criteria->find();
		}
	}

	/**
	 * Returns the entry's descendants.
	 *
	 * @param int|null $delta
	 * @return array
	 */
	public function getDescendants($delta = null)
	{
		if (!isset($this->_descendants))
		{
			if ($this->id)
			{
				$this->_descendants = craft()->entries->getEntryDescendants($this);
			}
			else
			{
				$this->_descendants = array();
			}
		}

		if ($delta)
		{
			return array_slice($this->_descendants, 0, $delta);
		}
		else
		{
			return $this->_descendants;
		}
	}

	/**
	 * Sets the entry's descendants.
	 *
	 * @param array $descendants
	 */
	public function setDescendants($descendants)
	{
		$this->_descendants = $descendants;
	}
}
