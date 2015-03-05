<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\base\Element;
use craft\app\enums\AttributeType;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * ElementCriteria model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementCriteria extends Model implements \Countable
{
	// Properties
	// =========================================================================

	/**
	 * @var integer Ancestor dist
	 */
	public $ancestorDist;

	/**
	 * @var array Ancestor of
	 */
	public $ancestorOf;

	/**
	 * @var boolean Archived
	 */
	public $archived = false;

	/**
	 * @var array Date created
	 */
	public $dateCreated;

	/**
	 * @var array Date updated
	 */
	public $dateUpdated;

	/**
	 * @var integer Descendant dist
	 */
	public $descendantDist;

	/**
	 * @var array Descendant of
	 */
	public $descendantOf;

	/**
	 * @var boolean Fixed order
	 */
	public $fixedOrder = false;

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Index by
	 */
	public $indexBy;

	/**
	 * @var integer Level
	 */
	public $level;

	/**
	 * @var integer Limit
	 */
	public $limit = 100;

	/**
	 * @var string Locale
	 */
	public $locale;

	/**
	 * @var boolean Locale enabled
	 */
	public $localeEnabled = true;

	/**
	 * @var array Next sibling of
	 */
	public $nextSiblingOf;

	/**
	 * @var integer Offset
	 */
	public $offset = 0;

	/**
	 * @var string Order
	 */
	public $order = 'elements.dateCreated desc';

	/**
	 * @var array Positioned after
	 */
	public $positionedAfter;

	/**
	 * @var array Positioned before
	 */
	public $positionedBefore;

	/**
	 * @var array Prev sibling of
	 */
	public $prevSiblingOf;

	/**
	 * @var array Related to
	 */
	public $relatedTo;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string Search
	 */
	public $search;

	/**
	 * @var array Sibling of
	 */
	public $siblingOf;

	/**
	 * @var string Slug
	 */
	public $slug;

	/**
	 * @var string Status
	 */
	public $status = 'enabled';

	/**
	 * @var string Title
	 */
	public $title;

	/**
	 * @var string URI
	 */
	public $uri;

	/**
	 * @var array Kind
	 */
	public $kind;

	// TODO: Deprecated. Remove these in Craft 4
	// -------------------------------------------------------------------------

	/**
	 * @var string Child field
	 */
	public $childField;

	/**
	 * @var array Child of
	 */
	public $childOf;

	/**
	 * @var integer Depth
	 */
	public $depth;

	/**
	 * @var string Parent field
	 */
	public $parentField;

	/**
	 * @var array Parent of
	 */
	public $parentOf;


	/**
	 * @var bool Whether this model should be strict about only allowing values to be set on defined attributes
	 */
	protected $strictAttributes = false;

	/**
	 * @var Element
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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['ancestorDist'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['descendantDist'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['level'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['limit'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['offset'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['depth'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['ancestorDist', 'ancestorOf', 'archived', 'dateCreated', 'dateUpdated', 'descendantDist', 'descendantOf', 'fixedOrder', 'id', 'indexBy', 'level', 'limit', 'locale', 'localeEnabled', 'nextSiblingOf', 'offset', 'order', 'positionedAfter', 'positionedBefore', 'prevSiblingOf', 'relatedTo', 'ref', 'search', 'siblingOf', 'slug', 'status', 'title', 'uri', 'kind', 'childField', 'childOf', 'depth', 'parentField', 'parentOf'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Constructor
	 *
	 * @param mixed   $attributes
	 * @param Element $elementType
	 *
	 * @return ElementCriteriaModel
	 */
	public function __construct($attributes, Element $elementType)
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
	 * In addition, will clears the cached values when a new attribute is set.
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
		if (in_array($name, $this->attributes()) && $this->$name === $value)
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
	 * @return Element
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
			$elements = Craft::$app->elements->findElements($this);
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
			$this->_cachedIds = Craft::$app->elements->findElements($this, true);
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
			$this->_cachedTotal = Craft::$app->elements->getTotalElements($this);
		}

		return $this->_cachedTotal;
	}

	/**
	 * Returns a copy of this model.
	 *
	 * @return Model
	 */
	public function copy()
	{
		$class = get_class($this);

		return new $class($this->getAttributes(), $this->_elementType);
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

	// Private Methods
	// =========================================================================

	/**
	 * @return null
	 */
	private function _includeInTemplateCaches()
	{
		if (Craft::$app->has('cache', true))
		{
			Craft::$app->templateCache->includeCriteriaInTemplateCaches($this);
		}
	}
}
