<?php
namespace Craft;

/**
 * Base element model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
abstract class BaseElementModel extends BaseModel
{
	// Constants
	// =========================================================================

	const ENABLED  = 'enabled';
	const DISABLED = 'disabled';
	const ARCHIVED = 'archived';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	protected $elementType;

	/**
	 * @var
	 */
	private $_fieldsByHandle;

	/**
	 * @var
	 */
	private $_contentPostLocation;

	/**
	 * @var
	 */
	private $_rawPostContent;

	/**
	 * @var
	 */
	private $_content;

	/**
	 * @var
	 */
	private $_preppedContent;

	/**
	 * @var
	 */
	private $_nextElement;

	/**
	 * @var
	 */
	private $_prevElement;

	/**
	 * @var
	 */
	private $_parent;

	/**
	 * @var
	 */
	private $_prevSibling;

	/**
	 * @var
	 */
	private $_nextSibling;

	/**
	 * @var
	 */
	private $_ancestorsCriteria;

	/**
	 * @var
	 */
	private $_descendantsCriteria;

	/**
	 * @var
	 */
	private $_childrenCriteria;

	/**
	 * @var
	 */
	private $_siblingsCriteria;

	// Public Methods
	// =========================================================================

	/**
	 * Treats custom fields as properties.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		if ($name == 'title' || parent::__isset($name) || $this->getFieldByHandle($name))
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
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function __get($name)
	{
		// Run through the BaseModel/CModel stuff first
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Is $name a field handle?
			if ($this->getFieldByHandle($name))
			{
				return $this->getFieldValue($name);
			}

			// Fine, throw the exception
			throw $e;
		}
	}

	/**
	 * Use the element's title as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->getTitle();
	}

	/**
	 * @inheritDoc BaseModel::populateModel()
	 *
	 * @param mixed $values
	 *
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		// Strip out the element record attributes if this is getting called from a child class based on an Active
		// Record result eager-loaded with the ElementRecord
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
	 * @return array
	 */
	public function getLocales()
	{
		if (craft()->elements->getElementType($this->elementType)->isLocalized())
		{
			return craft()->i18n->getSiteLocaleIds();
		}
		else
		{
			return array(craft()->i18n->getPrimarySiteLocaleId());
		}
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
			$path = ($this->uri == '__home__') ? '' : $this->uri;
			$url = UrlHelper::getSiteUrl($path, null, null, $this->locale);

			return $url;
		}
	}

	/**
	 * Returns an anchor pre-filled with this element's URL and title.
	 *
	 * @return \Twig_Markup
	 */
	public function getLink()
	{
		$link = '<a href="'.$this->getUrl().'">'.HtmlHelper::encode($this->__toString()).'</a>';
		return TemplateHelper::getRaw($link);
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
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return false;
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
	 *
	 * @return string|null
	 */
	public function getThumbUrl($size = null)
	{
		return null;
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
		else if (!$this->enabled || !$this->localeEnabled)
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
	 *
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
	 *
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
	 *
	 * @return null
	 */
	public function setNext($element)
	{
		$this->_nextElement = $element;
	}

	/**
	 * Sets the default previous element.
	 *
	 * @param BaseElementModel|false $element
	 *
	 * return void
	 */
	public function setPrev($element)
	{
		$this->_prevElement = $element;
	}

	/**
	 * Get the element's parent.
	 *
	 * @return BaseElementModel|null
	 */
	public function getParent()
	{
		if (!isset($this->_parent))
		{
			$parent = $this->getAncestors(1)->status(null)->localeEnabled(null)->first();

			if ($parent)
			{
				$this->_parent = $parent;
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
	 * Sets the element's parent.
	 *
	 * @param BaseElementModel|null $parent
	 *
	 * @return null
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;

		if ($parent)
		{
			$this->level = $parent->level + 1;
		}
		else
		{
			$this->level = 1;
		}
	}

	/**
	 * Returns the element's ancestors.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementCriteriaModel
	 */
	public function getAncestors($dist = null)
	{
		if (!isset($this->_ancestorsCriteria))
		{
			$this->_ancestorsCriteria = craft()->elements->getCriteria($this->elementType);
			$this->_ancestorsCriteria->ancestorOf = $this;
			$this->_ancestorsCriteria->locale     = $this->locale;
		}

		if ($dist)
		{
			return $this->_ancestorsCriteria->ancestorDist($dist);
		}
		else
		{
			return $this->_ancestorsCriteria;
		}
	}

	/**
	 * Returns the element's descendants.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementCriteriaModel
	 */
	public function getDescendants($dist = null)
	{
		if (!isset($this->_descendantsCriteria))
		{
			$this->_descendantsCriteria = craft()->elements->getCriteria($this->elementType);
			$this->_descendantsCriteria->descendantOf = $this;
			$this->_descendantsCriteria->locale       = $this->locale;
		}

		if ($dist)
		{
			return $this->_descendantsCriteria->descendantDist($dist);
		}
		else
		{
			return $this->_descendantsCriteria;
		}
	}

	/**
	 * Returns the element's children.
	 *
	 * @param mixed $field If this function is being used in the deprecated relationship-focused way, $field defines
	 *                     which field (if any) to limit the relationships by.
	 *
	 * @return ElementCriteriaModel
	 */
	public function getChildren($field = null)
	{
		// TODO: deprecated
		// Maintain support for the deprecated relationship-focussed getChildren() function for the element types that
		// were around before Craft 1.3
		if (
			($this->elementType == ElementType::Entry && $this->getSection()->type == SectionType::Channel) ||
			in_array($this->elementType, array(ElementType::Asset, ElementType::GlobalSet, ElementType::Tag, ElementType::User))
		)
		{
			craft()->deprecator->log('BaseElementModel::getChildren()_for_relations', 'Calling getChildren() to fetch an element’s target relations has been deprecated. Use the <a href="http://craftcms.com/docs/relations#the-relatedTo-param">relatedTo</a> param instead.');
			return $this->_getRelChildren($field);
		}
		else
		{
			if (!isset($this->_childrenCriteria))
			{
				$this->_childrenCriteria = $this->getDescendants(1);
			}

			return $this->_childrenCriteria;
		}
	}

	/**
	 * Returns all of the element's siblings.
	 *
	 * @return ElementCriteriaModel
	 */
	public function getSiblings()
	{
		if (!isset($this->_siblingsCriteria))
		{
			$this->_siblingsCriteria = craft()->elements->getCriteria($this->elementType);
			$this->_siblingsCriteria->siblingOf = $this;
			$this->_siblingsCriteria->locale    = $this->locale;
		}

		return $this->_siblingsCriteria;
	}

	/**
	 * Returns the element's previous sibling.
	 *
	 * @return BaseElementModel|null
	 */
	public function getPrevSibling()
	{
		if (!isset($this->_prevSibling))
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->prevSiblingOf = $this;
			$criteria->locale        = $this->locale;
			$criteria->status        = null;
			$criteria->localeEnabled = null;
			$this->_prevSibling = $criteria->first();
		}

		return $this->_prevSibling;
	}

	/**
	 * Returns the element's next sibling.
	 *
	 * @return BaseElementModel|null
	 */
	public function getNextSibling()
	{
		if (!isset($this->_nextSibling))
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->nextSiblingOf = $this;
			$criteria->locale        = $this->locale;
			$criteria->status        = null;
			$criteria->localeEnabled = null;
			$this->_nextSibling = $criteria->first();
		}

		return $this->_nextSibling;
	}

	/**
	 * Returns whether the element has descendants.
	 *
	 * @return bool
	 */
	public function hasDescendants()
	{
		return ($this->lft && $this->rgt && $this->rgt > $this->lft + 1);
	}

	/**
	 * Returns the total number of descendants that the element has.
	 *
	 * @return bool
	 */
	public function getTotalDescendants()
	{
		if ($this->hasDescendants())
		{
			return ($this->rgt - $this->lft - 1) / 2;
		}

		return 0;
	}

	/**
	 * Returns whether this element is an ancestor of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isAncestorOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->lft < $element->lft && $this->rgt > $element->rgt);
	}

	/**
	 * Returns whether this element is a descendant of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isDescendantOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->lft > $element->lft && $this->rgt < $element->rgt);
	}

	/**
	 * Returns whether this element is a direct parent of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isParentOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->level == $element->level - 1 && $this->isAncestorOf($element));
	}

	/**
	 * Returns whether this element is a direct child of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isChildOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->level == $element->level + 1 && $this->isDescendantOf($element));
	}

	/**
	 * Returns whether this element is a sibling of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isSiblingOf(BaseElementModel $element)
	{
		if ($this->root == $element->root && $this->level && $this->level == $element->level)
		{
			if ($this->level == 1 || $this->isPrevSiblingOf($element) || $this->isNextSiblingOf($element))
			{
				return true;
			}
			else
			{
				$parent = $this->getParent();

				if ($parent)
				{
					return $element->isDescendantOf($parent);
				}
			}
		}

		return false;
	}

	/**
	 * Returns whether this element is the direct previous sibling of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isPrevSiblingOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->level == $element->level && $this->rgt == $element->lft - 1);
	}

	/**
	 * Returns whether this element is the direct next sibling of another one.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public function isNextSiblingOf(BaseElementModel $element)
	{
		return ($this->root == $element->root && $this->level == $element->level && $this->lft == $element->rgt + 1);
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
	 * Treats custom fields as array offsets.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		if ($offset == 'title' || parent::offsetExists($offset) || $this->getFieldByHandle($offset))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritDoc BaseModel::getAttribute()
	 *
	 * @param string $name
	 * @param bool   $flattenValue
	 *
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		return parent::getAttribute($name, $flattenValue);
	}

	/**
	 * Returns the raw content saved on this entity.
	 *
	 * This is now deprecated. Use getContent() to get the ContentModel instead.
	 *
	 * @param string|null $fieldHandle
	 *
	 * @deprecated Deprecated in 2.0. Use {@link getContent()} instead.
	 * @return mixed
	 */
	public function getRawContent($fieldHandle = null)
	{
		craft()->deprecator->log('BaseElementModel::getRawContent()', 'BaseElementModel::getRawContent() has been deprecated. Use getContent() instead.');

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
				$this->_content = $this->createContent();
			}
		}

		return $this->_content;
	}

	/**
	 * Sets the content for the element.
	 *
	 * @param ContentModel|array $content
	 *
	 * @return null
	 */
	public function setContent($content)
	{
		if (is_array($content))
		{
			if (!isset($this->_content))
			{
				$this->_content = $this->createContent();
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
	 * @param array|string $content
	 *
	 * @return null
	 */
	public function setContentFromPost($content)
	{
		if (is_string($content))
		{
			// Keep track of where the post data is coming from, in case any field types need to know where to
			// look in $_FILES
			$this->setContentPostLocation($content);

			$content = craft()->request->getPost($content, array());
		}

		if (!isset($this->_rawPostContent))
		{
			$this->_rawPostContent = array();
		}

		$fieldLayout = $this->getFieldLayout();

		if ($fieldLayout)
		{
			// Make sure $this->_content is set
			$this->getContent();

			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();

				if ($field)
				{
					$handle = $field->handle;

					// Do we have any post data for this field?
					if (isset($content[$handle]))
					{
						$value = $content[$handle];
						$this->setRawPostContent($handle, $value);
					}
					// Were any files uploaded for this field?
					else if (!empty($this->_contentPostLocation) && UploadedFile::getInstancesByName($this->_contentPostLocation.'.'.$handle))
					{
						$value = null;
					}
					else
					{
						// No data was submitted so just skip this field
						continue;
					}

					// Give the field type a chance to make changes
					$fieldType = $field->getFieldType();

					if ($fieldType)
					{
						$fieldType->element = $this;
						$value = $fieldType->prepValueFromPost($value);
					}

					// Now set the prepped value on the ContentModel
					$this->_content->$handle = $value;
				}
			}
		}
	}

	/**
	 * Sets a field’s raw post content.
	 *
	 * @param string $handle The field handle.
	 * @param string|array   The posted field value.
	 */
	public function setRawPostContent($handle, $value)
	{
		$this->_rawPostContent[$handle] = $value;
	}

	/**
	 * Returns the raw content from the post data, before it was passed through {@link prepValueFromPost()}.
	 *
	 * @return array
	 */
	public function getContentFromPost()
	{
		if (isset($this->_rawPostContent))
		{
			return $this->_rawPostContent;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Returns the location in POST that the content was pulled from.
	 *
	 * @return string|null
	 */
	public function getContentPostLocation()
	{
		return $this->_contentPostLocation;
	}

	/**
	 * Sets the location in POST that the content was pulled from.
	 *
	 * @param $contentPostLocation
	 *
	 * @return string|null
	 */
	public function setContentPostLocation($contentPostLocation)
	{
		$this->_contentPostLocation = $contentPostLocation;
	}

	/**
	 * Returns the prepped content for a given field.
	 *
	 * @param string $fieldHandle
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function getFieldValue($fieldHandle)
	{
		if (!isset($this->_preppedContent) || !array_key_exists($fieldHandle, $this->_preppedContent))
		{
			$field = $this->getFieldByHandle($fieldHandle);

			if (!$field)
			{
				throw new Exception(Craft::t('No field exists with the handle “{handle}”', array('handle' => $fieldHandle)));
			}

			$content = $this->getContent();

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

			$this->_preppedContent[$fieldHandle] = $value;
		}

		return $this->_preppedContent[$fieldHandle];
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
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type parents.
	 *
	 * @param mixed $field
	 *
	 * @deprecated Deprecated in 1.3. Use the [relatedTo](http://craftcms.com/docs/relations#the-relatedTo-param)
	 *             param instead.
	 *
	 * @return ElementCriteriaModel
	 */
	public function getParents($field = null)
	{
		craft()->deprecator->log('BaseElementModel::getParents()', 'Calling getParents() to fetch an element’s source relations has been deprecated. Use the <a href="http://craftcms.com/docs/relations#the-relatedTo-param">relatedTo</a> param instead.');

		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->parentOf    = $this;
		$criteria->parentField = $field;
		return $criteria;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the field with a given handle.
	 *
	 * @param string $handle
	 *
	 * @return FieldModel|null
	 */
	protected function getFieldByHandle($handle)
	{
		if (!isset($this->_fieldsByHandle) || !array_key_exists($handle, $this->_fieldsByHandle))
		{
			$contentService = craft()->content;

			$originalFieldContext = $contentService->fieldContext;
			$contentService->fieldContext = $this->getFieldContext();

			$this->_fieldsByHandle[$handle] = craft()->fields->getFieldByHandle($handle);

			$contentService->fieldContext = $originalFieldContext;
		}

		return $this->_fieldsByHandle[$handle];
	}


	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'enabled'       => array(AttributeType::Bool, 'default' => true),
			'archived'      => array(AttributeType::Bool, 'default' => false),
			'locale'        => array(AttributeType::Locale, 'default' => craft()->i18n->getPrimarySiteLocaleId()),
			'localeEnabled' => array(AttributeType::Bool, 'default' => true),
			'slug'          => array(AttributeType::String, 'label' => 'Slug'),
			'uri'           => array(AttributeType::String, 'label' => 'URI'),
			'dateCreated'   => AttributeType::DateTime,
			'dateUpdated'   => AttributeType::DateTime,

			'root'          => AttributeType::Number,
			'lft'           => AttributeType::Number,
			'rgt'           => AttributeType::Number,
			'level'         => AttributeType::Number,

			'searchScore'   => AttributeType::Number,
		);
	}

	/**
	 * Creates the content model associated with this element.
	 *
	 * @return ContentModel The content model associated with this element
	 */
	protected function createContent()
	{
		return craft()->content->createContent($this);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a new ElementCriteriaModel prepped to return this element's same-type children.
	 *
	 * @param mixed $field
	 *
	 * @deprecated Deprecated in 1.3. Use {@link getChildren()} instead.
	 * @return ElementCriteriaModel
	 */
	private function _getRelChildren($field = null)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->childOf    = $this;
		$criteria->childField = $field;
		return $criteria;
	}

	/**
	 * Returns an element right before/after this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 * @param int   $dir
	 *
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
				// Create a new criteria regardless of whether they passed in an ElementCriteriaModel so that our 'id'
				// modification doesn't stick
				$criteria = craft()->elements->getCriteria($this->elementType, $criteria);

				$criteria->id = $elementIds[$key+$dir];

				return $criteria->first();
			}
		}
	}
}
