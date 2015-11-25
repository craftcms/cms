<?php
namespace Craft;

/**
 * Element criteria model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class ElementCriteriaModel extends BaseModel implements \Countable
{
	// Properties
	// =========================================================================

	/**
	 * @var bool Whether this model should be strict about only allowing values to be set on defined attributes
	 */
	protected $strictAttributes = false;

	/**
	 * @var BaseElementType
	 */
	private $_elementType;

	/**
	 * @var
	 */
	private $_supportedFieldHandles;

	/**
	 * @var
	 */
	private $_matchedElements;

	/**
	 * @var
	 */
	private $_matchedElementsAtOffsets;

	/**
	 * @var
	 */
	private $_cachedIds;

	/**
	 * @var
	 */
	private $_cachedTotal;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param mixed           $attributes
	 * @param BaseElementType $elementType
	 *
	 * @return ElementCriteriaModel
	 */
	public function __construct($attributes, BaseElementType $elementType)
	{
		$this->_elementType = $elementType;

		parent::__construct($attributes);
	}

	/**
	 * Returns an iterator for traversing over the elements.
	 *
	 * Required by the IteratorAggregate interface.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->find());
	}

	/**
	 * Returns whether an element exists at a given offset. Required by the ArrayAccess interface.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		if (is_numeric($offset))
		{
			return ($this->nth($offset) !== null);
		}
		else
		{
			return parent::offsetExists($offset);
		}
	}

	/**
	 * Returns the element at a given offset. Required by the ArrayAccess interface.
	 *
	 * @param mixed $offset
	 *
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if (is_numeric($offset))
		{
			return $this->nth($offset);
		}
		else
		{
			return parent::offsetGet($offset);
		}
	}

	/**
	 * Sets the element at a given offset. Required by the ArrayAccess interface.
	 *
	 * @param mixed $offset
	 * @param mixed $item
	 *
	 * @return null
	 */
	public function offsetSet($offset, $item)
	{
		if (is_numeric($offset) && $item instanceof BaseElementModel)
		{
			$this->_matchedElementsAtOffsets[$offset] = $item;
		}
		else
		{
			return parent::offsetSet($offset, $item);
		}
	}

	/**
	 * Unsets an element at a given offset. Required by the ArrayAccess interface.
	 *
	 * @param mixed $offset
	 *
	 * @return null
	 */
	public function offsetUnset($offset)
	{
		if (is_numeric($offset))
		{
			unset($this->_matchedElementsAtOffsets[$offset]);
		}
		else
		{
			return parent::offsetUnset($offset);
		}
	}

	/**
	 * Returns the total number of elements matched by this criteria. Required by the Countable interface.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->find());
	}

	/**
	 * Sets an attribute's value.
	 *
	 * In addition, clears the cached values when a new attribute is set.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		// If this is an attribute, and the value is not actually changing, just return true so the matched elements
		// don't get cleared.
		if (in_array($name, $this->attributeNames()) && $this->getAttribute($name) === $value)
		{
			return true;
		}

		if (parent::setAttribute($name, $value))
		{
			$this->_matchedElements = null;
			$this->_matchedElementsAtOffsets = null;
			$this->_cachedIds = null;
			$this->_cachedTotal = null;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getElementType()
	 *
	 * @return BaseElementType
	 */
	public function getElementType()
	{
		return $this->_elementType;
	}

	/**
	 * Returns the field handles that didn't conflict with the main attribute names.
	 *
	 * @return array
	 */
	public function getSupportedFieldHandles()
	{
		return $this->_supportedFieldHandles;
	}

	/**
	 * language => locale
	 *
	 * @param $locale
	 *
	 * @return ElementCriteriaModel
	 */
	public function setLanguage($locale)
	{
		$this->setAttribute('locale', $locale);

		return $this;
	}

	/**
	 * Returns all elements that match the criteria.
	 *
	 * @param array $attributes Any last-minute parameters that should be added.
	 *
	 * @return array The matched elements.
	 */
	public function find($attributes = null)
	{
		$this->setAttributes($attributes);

		$this->_includeInTemplateCaches();

		if (!isset($this->_matchedElements))
		{
			$elements = craft()->elements->findElements($this);
			$this->setMatchedElements($elements);
		}

		return $this->_matchedElements;
	}

	/**
	 * Returns an element at a specific offset.
	 *
	 * @param int $offset The offset.
	 *
	 * @return BaseElementModel|null The element, if there is one.
	 */
	public function nth($offset)
	{
		if (!isset($this->_matchedElementsAtOffsets) || !array_key_exists($offset, $this->_matchedElementsAtOffsets))
		{
			$criteria = new ElementCriteriaModel($this->getAttributes(), $this->_elementType);
			$criteria->offset = $offset;
			$criteria->limit = 1;
			$elements = $criteria->find();

			if ($elements)
			{
				$this->_matchedElementsAtOffsets[$offset] = $elements[0];
			}
			else
			{
				$this->_matchedElementsAtOffsets[$offset] = null;
			}
		}

		return $this->_matchedElementsAtOffsets[$offset];
	}

	/**
	 * Returns the first element that matches the criteria.
	 *
	 * @param array|null $attributes
	 *
	 * @return BaseElementModel|null
	 */
	public function first($attributes = null)
	{
		$this->setAttributes($attributes);

		return $this->nth(0);
	}

	/**
	 * Returns the last element that matches the criteria.
	 *
	 * @param array|null $attributes
	 *
	 * @return BaseElementModel|null
	 */
	public function last($attributes = null)
	{
		$this->setAttributes($attributes);

		$total = $this->total();

		if ($total)
		{
			return $this->nth($total-1);
		}
	}

	/**
	 * Returns all element IDs that match the criteria.
	 *
	 * @param array|null $attributes
	 *
	 * @return array
	 */
	public function ids($attributes = null)
	{
		$this->setAttributes($attributes);

		$this->_includeInTemplateCaches();

		if (!isset($this->_cachedIds))
		{
			$this->_cachedIds = craft()->elements->findElements($this, true);
		}

		return $this->_cachedIds;
	}

	/**
	 * Returns the total elements that match the criteria.
	 *
	 * @param array|null $attributes
	 *
	 * @return int
	 */
	public function total($attributes = null)
	{
		$this->setAttributes($attributes);

		$this->_includeInTemplateCaches();

		if (!isset($this->_cachedTotal))
		{
			$this->_cachedTotal = craft()->elements->getTotalElements($this);
		}

		return $this->_cachedTotal;
	}

	/**
	 * Returns a copy of this model.
	 *
	 * @return BaseModel
	 */
	public function copy()
	{
		$class = get_class($this);
		$copy = new $class($this->getAttributes(), $this->_elementType);

		if ($this->_matchedElements !== null)
		{
			$copy->setMatchedElements($this->_matchedElements);
		}

		return $copy;
	}

	/**
	 * Stores the matched elements to avoid redundant DB queries.
	 *
	 * @param array $elements The matched elements.
	 *
	 * @return null
	 */
	public function setMatchedElements($elements)
	{
		$this->_matchedElements = $elements;

		// Store them by offset, too
		$offset = $this->offset;

		foreach ($this->_matchedElements as $element)
		{
			$this->_matchedElementsAtOffsets[$offset] = $element;
			$offset++;
		}
	}

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Returns an element at a specific offset.
	 *
	 * @param int $offset
	 *
	 * @deprecated Deprecated in 2.2. Use {@link nth()} instead.
	 * @return BaseElementModel|null
	 *
	 */
	public function findElementAtOffset($offset)
	{
		craft()->deprecator->log('ElementCriteriaModel::findElementAtOffset()', 'ElementCriteriaModel::findElementAtOffset() has been deprecated. Use nth() instead.');
		return $this->nth($offset);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = array(
			'ancestorDist'     => AttributeType::Number,
			'ancestorOf'       => AttributeType::Mixed,
			'archived'         => AttributeType::Bool,
			'dateCreated'      => AttributeType::Mixed,
			'dateUpdated'      => AttributeType::Mixed,
			'descendantDist'   => AttributeType::Number,
			'descendantOf'     => AttributeType::Mixed,
			'fixedOrder'       => AttributeType::Bool,
			'id'               => AttributeType::Number,
			'indexBy'          => AttributeType::String,
			'level'            => AttributeType::Number,
			'limit'            => array(AttributeType::Number, 'default' => 100),
			'locale'           => AttributeType::Locale,
			'localeEnabled'    => array(AttributeType::Bool, 'default' => true),
			'nextSiblingOf'    => AttributeType::Mixed,
			'offset'           => array(AttributeType::Number, 'default' => 0),
			'order'            => array(AttributeType::String, 'default' => 'elements.dateCreated desc'),
			'positionedAfter'  => AttributeType::Mixed,
			'positionedBefore' => AttributeType::Mixed,
			'prevSiblingOf'    => AttributeType::Mixed,
			'relatedTo'        => AttributeType::Mixed,
			'ref'              => AttributeType::String,
			'search'           => AttributeType::String,
			'siblingOf'        => AttributeType::Mixed,
			'slug'             => AttributeType::String,
			'status'           => array(AttributeType::String, 'default' => BaseElementModel::ENABLED),
			'title'            => AttributeType::String,
			'uri'              => AttributeType::String,
			'kind'             => AttributeType::Mixed,

			// TODO: Deprecated
			'childField'       => AttributeType::String,
			'childOf'          => AttributeType::Mixed,
			'depth'            => AttributeType::Number,
			'parentField'      => AttributeType::String,
			'parentOf'         => AttributeType::Mixed,
		);

		// Mix in any custom attributes defined by the element type
		$elementTypeAttributes = $this->_elementType->defineCriteriaAttributes();
		$attributes = array_merge($attributes, $elementTypeAttributes);

		return $attributes;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return null
	 */
	private function _includeInTemplateCaches()
	{
		$cacheService = craft()->getComponent('templateCache', false);

		if ($cacheService)
		{
			$cacheService->includeCriteriaInTemplateCaches($this);
		}
	}
}
