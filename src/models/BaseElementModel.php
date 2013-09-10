<?php
namespace Craft;

/**
 * Base element model class
 */
abstract class BaseElementModel extends BaseModel
{
	protected $elementType;

	private $_content;
	private $_preppedContent;
	private $_tags;

	const ENABLED  = 'enabled';
	const DISABLED = 'disabled';
	const ARCHIVED = 'archived';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'          => AttributeType::Number,
			//'type'        => array(AttributeType::String, 'default' => $this->elementType),
			'enabled'     => array(AttributeType::Bool, 'default' => true),
			'archived'    => array(AttributeType::Bool, 'default' => false),
			'locale'      => array(AttributeType::Locale, 'default' => craft()->i18n->getPrimarySiteLocaleId()),
			'uri'         => AttributeType::String,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		);
	}

	/**
	 * Use the entry's title as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->getTitle();
	}

	/**
	 * Returns the type of element this is.
	 *
	 * @return string
	 */
	public function getElementType()
	{
		return $this->elementType;
	}

	/**
	 * Returns the element's full URL.
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
	 * Returns an anchor prefilled with this element's URL and title.
	 *
	 * @return string
	 */
	public function getLink()
	{
		return '<a href="'.$this->getUrl().'">'.$this->__toString().'</a>';
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return false;
	}

	/**
	 * Returns the URL to the element's thumbnail, if there is one.
	 *
	 * @param int|null $size
	 * @return string|false
	 */
	public function getThumbUrl($size = null)
	{
		return false;
	}

	/**
	 * Returns the URL to the element's icon image, if there is one.
	 *
	 * @param int|null $size
	 * @return string|false
	 */
	public function getIconUrl($size = null)
	{
		return false;
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		if ($this->archived)
		{
			return static::ARCHIVED;
		}
		else if (!$this->enabled)
		{
			return static::DISABLED;
		}
		else
		{
			return static::ENABLED;
		}
	}

	/**
	 * Returns the next element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 * @return ElementCriteriaModel|null
	 */
	public function getNext($criteria = null)
	{
		return $this->_getRelativeElement($criteria, 1);
	}

	/**
	 * Returns the previous element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 * @return ElementCriteriaModel|null
	 */
	public function getPrev($criteria = null)
	{
		return $this->_getRelativeElement($criteria, -1);
	}

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type children.
	 *
	 * @param mixed $field
	 * @return ElementCriteriaModel
	 */
	public function getChildren($field = null)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->childOf($this);
		$criteria->childField($field);
		return $criteria;
	}

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type parents.
	 *
	 * @param mixed $field
	 * @return ElementCriteriaModel
	 */
	public function getParents($field = null)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->parentOf($this);
		$criteria->parentField($field);
		return $criteria;
	}

	/**
	 * Returns the element's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		$content = $this->getContent();
		return $content->title;
	}

	/**
	 * Treats custom fields as properties.
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (parent::__isset($name) || craft()->fields->getFieldByHandle($name))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Treats custom fields as array offsets.
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		if (parent::offsetExists($offset) || craft()->fields->getFieldByHandle($offset))
		{
			return true;
		}
		else
		{
			return false;
		}
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
			$field = craft()->fields->getFieldByHandle($name);
			if ($field)
			{
				return $this->_getPreppedContentForField($field);
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
		$content = $this->getContent();

		if ($fieldHandle)
		{
			if (isset($content->$fieldHandle))
			{
				return $content->$fieldHandle;
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
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		// Strip out the element record attributes if this is getting called from a child class
		// based on an Active Record result eager-loaded with the ElementRecord
		if (isset($values['element']))
		{
			$elementAttributes = $values['element'];
			unset($values['element']);
		}

		$model = parent::populateModel($values);

		// Now set those ElementRecord attributes
		if (isset($elementAttributes))
		{
			if (isset($elementAttributes['i18n']))
			{
				$model->setAttributes($elementAttributes['i18n']);
				unset($elementAttributes['i18n']);
			}

			$model->setAttributes($elementAttributes);
		}

		return $model;
	}

	/**
	 * Returns an element right before/after this one, from a given set of criteria.
	 *
	 * @access private
	 * @param mixed $criteria
	 * @param int $dir
	 * @return BaseElementModel|null
	 */
	private function _getRelativeElement($criteria, $dir)
	{
		if ($this->id)
		{
			if (!($criteria instanceof ElementCriteriaModel))
			{
				$criteria = craft()->elements->getCriteria($this->elementType, $criteria);
			}

			$elementIds = $criteria->ids();
			$key = array_search($this->id, $elementIds);

			if ($key !== false && isset($elementIds[$key+$dir]))
			{
				$criteria->id = $elementIds[$key+$dir];
				return $criteria->first();
			}
		}
	}

	/**
	 * Returns the content for the element.
	 *
	 * @return ContentModel
	 */
	public function getContent()
	{
		if (!isset($this->_content))
		{
			if ($this->id)
			{
				$this->_content = craft()->content->getElementContent($this->id, $this->locale);
			}

			if (empty($this->_content))
			{
				$this->_content = new ContentModel();
				$this->_content->elementId = $this->id;
				$this->_content->locale = $this->locale;
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
			$content = $this->getContent();
			$fieldHandle = $field->handle;

			if (isset($content->$fieldHandle))
			{
				$value = $content->$fieldHandle;
			}
			else
			{
				$value = null;
			}

			$fieldType = craft()->fields->populateFieldType($field, $this);

			if ($fieldType)
			{
				$value = $fieldType->prepValue($value);
			}

			$this->_preppedContent[$field->handle] = $value;
		}

		return $this->_preppedContent[$field->handle];
	}
}
