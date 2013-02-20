<?php
namespace Blocks;

/**
 * Entry model class
 */
class EntryModel extends BaseModel
{
	protected $entryType;

	private $_content;
	private $_preppedContent;
	private $_tags;

	/**
	 * Use the entry's title as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->title;
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'          => AttributeType::Number,
			'type'        => array(AttributeType::String, 'default' => $this->entryType),
			'postDate'    => array(AttributeType::DateTime, 'default' => new DateTime()),
			'expiryDate'  => AttributeType::DateTime,
			'enabled'     => array(AttributeType::Bool, 'default' => true),
			'archived'    => array(AttributeType::Bool, 'default' => false),
			'locale'      => AttributeType::Locale,
			'title'       => AttributeType::String,
			'uri'         => AttributeType::String,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		);
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
			return blx()->users->getUserById($this->authorId);
		}
	}

	/**
	 * Returns the entry's full URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->uri !== null)
		{
			return UrlHelper::getSiteUrl($this->uri);
		}
	}

	/**
	 * Returns the entry's CP edit URL.
	 *
	 * @return string|null
	 */
	public function getCpEditUrl()
	{
		if ($this->id)
		{
			return blx()->entries->getCpEditUrlForEntry($this);
		}
	}

	/**
	 * Returns the entry's status.
	 *
	 * @return string
	 */
	public function getStatus()
	{
		if ($this->enabled)
		{
			$currentTime = DateTimeHelper::currentTimeStamp();
			$postDate = ($this->postDate ? $this->postDate->getTimestamp() : null);
			$expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

			if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
			{
				return 'live';
			}
			else if ($postDate && $postDate > $currentTime)
			{
				return 'pending';
			}
			/* HIDE */
			//else if ($expiryDate && $expiryDate <= $currentTime)
			/* end HIDE */
			else
			{
				return 'expired';
			}
		}
		else
		{
			return 'disabled';
		}
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
				$this->_tags = blx()->entries->getTagsByEntryId($this->id);
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
	 * Is set?
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (parent::__isset($name))
		{
			return true;
		}

		// Is $name a field handle?
		$field = blx()->fields->getFieldByHandle($name);
		if ($field)
		{
			return true;
		}

		// Is $name a RTL link handle?
		$linkCriteria = blx()->links->getCriteriaByTypeAndHandle($this->getAttribute('type'), $name, 'rtl');
		if ($linkCriteria)
		{
			return true;
		}

		return false;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return mixed
	 */
	function __get($name)
	{
		// Run through the BaseModel/CModel stuff first
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Is $name a field handle?
			$field = blx()->fields->getFieldByHandle($name);
			if ($field)
			{
				return $this->_getPreppedContentForField($field);
			}
			else if ($this->getAttribute('id'))
			{
				// Is $name a RTL link handle?
				$linkCriteria = blx()->links->getCriteriaByTypeAndHandle($this->getAttribute('type'), $name, 'rtl');

				if ($linkCriteria)
				{
					return blx()->links->getLinkedEntries($linkCriteria, $this->getAttribute('id'), 'rtl');
				}
			}

			// Fine, throw the exception
			throw $e;
		}
	}

	/**
	 * Returns the raw content saved on this entity.
	 *
	 * @param string|null $fieldHandle
	 * @return mixed
	 */
	public function getRawContent($fieldHandle = null)
	{
		$content = $this->_getContent();

		if ($fieldHandle)
		{
			if (isset($content[$fieldHandle]))
			{
				return $content[$fieldHandle];
			}
			else
			{
				return null;
			}
		}
		else
		{
			return $content;
		}
	}

	/**
	 * Sets content that's indexed by the field ID.
	 *
	 * @param array $content
	 */
	public function setContentIndexedByFieldId($content)
	{
		$this->_content = array();

		foreach ($content as $fieldId => $value)
		{
			$field = blx()->fields->getFieldById($fieldId);
			if ($field)
			{
				$this->_content[$field->handle] = $value;
			}
		}
	}

	/**
	 * Sets the content.
	 *
	 * @param array $content
	 */
	public function setContent($content)
	{
		$this->_content = $content;
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		$class = get_called_class();
		$model = new $class();

		if (isset($values['entry']))
		{
			if (isset($values['entry']['i18n']))
			{
				$model->setAttributes($values['entry']['i18n']);
				unset($values['entry']['i18n']);
			}

			$model->setAttributes($values['entry']);
			unset($values['entry']);
		}

		$model->setAttributes($values);
		return $model;
	}

	/**
	 * Returns the content for the entry.
	 *
	 * @access private
	 * @return array
	 */
	private function _getContent()
	{
		if (!isset($this->_content))
		{
			if ($this->id)
			{
				$this->_content = blx()->entries->getEntryContent($this->id, $this->locale);
			}
			else
			{
				$this->_content = array();
			}
		}

		return $this->_content;
	}

	/**
	 * Returns the prepped content for a given field.
	 *
	 * @param FieldModel $field
	 * @return mixed
	 */
	private function _getPreppedContentForField(FieldModel $field)
	{
		if (!isset($this->_preppedContent) || !array_key_exists($field->handle, $this->_preppedContent))
		{
			$content = $this->_getContent();

			if (isset($content[$field->handle]))
			{
				$value = $content[$field->handle];
			}
			else
			{
				$value = null;
			}

			$fieldType = blx()->fields->populateFieldType($field, $this);

			if ($fieldType)
			{
				$value = $fieldType->prepValue($value);
			}

			$this->_preppedContent[$field->handle] = $value;
		}

		return $this->_preppedContent[$field->handle];
	}
}
