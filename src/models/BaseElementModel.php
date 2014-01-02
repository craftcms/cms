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

	private $_nextElement;
	private $_prevElement;

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
			'enabled'     => array(AttributeType::Bool, 'default' => true),
			'archived'    => array(AttributeType::Bool, 'default' => false),
			'locale'      => array(AttributeType::Locale, 'default' => craft()->i18n->getPrimarySiteLocaleId()),
			'slug'        => AttributeType::String,
			'uri'         => AttributeType::String,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,

			'root'        => AttributeType::Number,
			'lft'         => AttributeType::Number,
			'rgt'         => AttributeType::Number,
			'level'       => AttributeType::Number,
		);
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
	 * Use the entry's title as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return (string) $this->getTitle();
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
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		return craft()->fields->getLayoutByType($this->elementType);
	}

	/**
	 * Returns the locale IDs this element is available in.
	 *
	 * @return array|null
	 */
	public function getLocales()
	{
	}

	/**
	 * Returns the URL format used to generate this element's URL.
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
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
			if ($this->uri == '__home__')
			{
				return UrlHelper::getSiteUrl();
			}
			else
			{
				return UrlHelper::getSiteUrl($this->uri);
			}
		}
	}

	/**
	 * Returns an anchor prefilled with this element's URL and title.
	 *
	 * @return \Twig_Markup
	 */
	public function getLink()
	{
		$link = '<a href="'.$this->getUrl().'">'.$this->__toString().'</a>';
		$charset = craft()->templates->getTwig()->getCharset();
		return new \Twig_Markup($link, $charset);
	}

	/**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
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
	public function getNext($criteria = false)
	{
		if ($criteria !== false || !isset($this->_nextElement))
		{
			return $this->_getRelativeElement($criteria, 1);
		}
		else if ($this->_nextElement !== false)
		{
			return $this->_nextElement;
		}
	}

	/**
	 * Returns the previous element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 * @return ElementCriteriaModel|null
	 */
	public function getPrev($criteria = false)
	{
		if ($criteria !== false || !isset($this->_prevElement))
		{
			return $this->_getRelativeElement($criteria, -1);
		}
		else if ($this->_prevElement !== false)
		{
			return $this->_prevElement;
		}
	}

	/**
	 * Sets the default next element.
	 *
	 * @param BaseElementModel|false $element
	 */
	public function setNext($element)
	{
		$this->_nextElement = $element;
	}

	/**
	 * Sets the default previous element.
	 *
	 * @param BaseElementModel|false $element
	 */
	public function setPrev($element)
	{
		$this->_prevElement = $element;
	}

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type children.
	 *
	 * @deprecated
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
	 * @deprecated
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
		if (parent::__isset($name) || $this->getFieldByHandle($name))
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
		if (parent::offsetExists($offset) || $this->getFieldByHandle($offset))
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
			$field = $this->getFieldByHandle($name);

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
	 * This is now deprecated. Use getContent() to get the ContentModel instead.
	 *
	 * @deprecated
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
	 * Returns the content for the element.
	 *
	 * @return ContentModel
	 */
	public function getContent()
	{
		if (!isset($this->_content))
		{
			$this->_content = craft()->content->getContent($this);

			if (!$this->_content)
			{
				$this->_content = craft()->content->createContent($this);
			}
		}

		return $this->_content;
	}

	/**
	 * Sets the content for the element.
	 *
	 * @param ContentModel|array $content
	 */
	public function setContent($content)
	{
		if (is_array($content))
		{
			if (!isset($this->_content))
			{
				$this->_content = craft()->content->createContent($this);
			}

			$this->_content->setAttributes($content);
		}
		else if ($content instanceof ContentModel)
		{
			$this->_content = $content;
		}
	}

	/**
	 * Sets the content from post data, calling prepValueFromPost() on the field types.
	 *
	 * @param array $content
	 */
	public function setContentFromPost($content)
	{
		$fieldLayout = $this->getFieldLayout();

		if ($fieldLayout)
		{
			if (!isset($this->_content))
			{
				$this->_content = $this->getContent();
			}

			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();

				if ($field)
				{
					$handle = $field->handle;

					if (isset($content[$handle]))
					{
						$this->_content->$handle = $content[$handle];
					}
					else
					{
						$this->_content->$handle = null;
					}

					// Give the field type a chance to make changes
					$fieldType = $field->getFieldType();

					if ($fieldType)
					{
						$fieldType->element = $this;
						$this->_content->$handle = $fieldType->prepValueFromPost($this->_content->$handle);
					}
				}
			}
		}
	}

	/**
	 * Returns the name of the table this element's content is stored in.
	 *
	 * @return string
	 */
	public function getContentTable()
	{
		return craft()->content->contentTable;
	}

	/**
	 * Returns the field column prefix this element's content uses.
	 *
	 * @return string
	 */
	public function getFieldColumnPrefix()
	{
		return craft()->content->fieldColumnPrefix;
	}

	/**
	 * Returns the field context this element's content uses.
	 *
	 * @return string
	 */
	public function getFieldContext()
	{
		return craft()->content->fieldContext;
	}

	/**
	 * Returns the field with a given handle.
	 *
	 * @access protected
	 * @param string $handle
	 * @return FieldModel|null
	 */
	protected function getFieldByHandle($handle)
	{
		$contentService = craft()->content;

		$originalFieldContext = $contentService->fieldContext;
		$contentService->fieldContext = $this->getFieldContext();

		$field = craft()->fields->getFieldByHandle($handle);

		$contentService->fieldContext = $originalFieldContext;

		return $field;
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
			if (!$criteria instanceof ElementCriteriaModel)
			{
				$criteria = craft()->elements->getCriteria($this->elementType, $criteria);
			}

			$elementIds = $criteria->ids();
			$key = array_search($this->id, $elementIds);

			if ($key !== false && isset($elementIds[$key+$dir]))
			{
				// Create a new criteria regardless of whether they passed in an ElementCriteriaModel
				// so that our 'id' modification doesn't stick
				$criteria = craft()->elements->getCriteria($this->elementType, $criteria);

				$criteria->id = $elementIds[$key+$dir];
				return $criteria->first();
			}
		}
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

			// Give the field type a chance to prep the value for use
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$fieldType->element = $this;
				$value = $fieldType->prepValue($value);
			}

			$this->_preppedContent[$field->handle] = $value;
		}

		return $this->_preppedContent[$field->handle];
	}
}
