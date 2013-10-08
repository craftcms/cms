<?php
namespace Craft;

/**
 * Element criteria model class
 */
class ElementCriteriaModel extends BaseModel
{
	private $_elementType;

	private $_supportedFieldHandles;
	private $_idsCache;
	private $_totalCache;

	/**
	 * Constructor
	 *
	 * @param mixed $attributes
	 * @param BaseElementType $elementType
	 */
	function __construct($attributes, BaseElementType $elementType)
	{
		$this->_elementType = $elementType;
		parent::__construct($attributes);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = array(
			'archived'      => AttributeType::Bool,
			'childField'    => AttributeType::String,
			'childOf'       => AttributeType::Mixed,
			'dateCreated'   => AttributeType::Mixed,
			'dateUpdated'   => AttributeType::Mixed,
			'fixedOrder'    => AttributeType::Bool,
			'id'            => AttributeType::Number,
			'indexBy'       => AttributeType::String,
			'limit'         => array(AttributeType::Number, 'default' => 100),
			'locale'        => AttributeType::Locale,
			'offset'        => array(AttributeType::Number, 'default' => 0),
			'order'         => array(AttributeType::String, 'default' => 'dateCreated desc'),
			'parentField'   => AttributeType::String,
			'parentOf'      => AttributeType::Mixed,
			'ref'           => AttributeType::String,
			'search'        => AttributeType::String,
			'status'        => array(AttributeType::String, 'default' => BaseElementModel::ENABLED),
			'uri'           => AttributeType::String,
		);

		// Mix in any custom attributes defined by the element type
		$elementTypeAttributes = $this->_elementType->defineCriteriaAttributes();
		$attributes = array_merge($attributes, $elementTypeAttributes);

		// Mix in the custom fields
		$this->_supportedFieldHandles = array();

		foreach (craft()->fields->getAllFields() as $field)
		{
			// Make sure the handle doesn't conflict with an existing attribute
			if (!isset($attributes[$field->handle]))
			{
				$this->_supportedFieldHandles[] = $field->handle;
				$attributes[$field->handle] = array(AttributeType::Mixed, 'default' => false);
			}
		}

		return $attributes;
	}

	/**
	 * Clears the cached values when a new attribute is set.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		if (parent::setAttribute($name, $value))
		{
			$this->_idsCache = null;
			$this->_totalCache = null;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the element type.
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
	 */
	public function setLanguage($locale)
	{
		$this->setAttribute('locale', $locale);
		return $this;
	}

	/**
	 * Returns all elements that match the criteria.
	 *
	 * @param array|null $attributes
	 * @return array
	 */
	public function find($attributes = null)
	{
		$this->setAttributes($attributes);
		return craft()->elements->findElements($this);
	}

	/**
	 * Returns all element IDs that match the criteria.
	 *
	 * @param array|null $attributes
	 * @return array
	 */
	public function ids($attributes = null)
	{
		$this->setAttributes($attributes);

		if (!isset($this->_idsCache))
		{
			$this->_idsCache = craft()->elements->findElements($this, true);
		}

		return $this->_idsCache;
	}

	/**
	 * Returns the first element that matches the criteria.
	 *
	 * @param array|null $attributes
	 * @return BaseElementModel|null
	 */
	public function first($attributes = null)
	{
		$this->setAttributes($attributes);
		$this->limit = 1;
		$elements = $this->find();

		if ($elements)
		{
			return $elements[0];
		}
	}

	/**
	 * Returns the last element that matches the criteria.
	 *
	 * @param array|null $attributes
	 * @return BaseElementModel|null
	 */
	public function last($attributes = null)
	{
		$this->setAttributes($attributes);

		if ($order = $this->order)
		{
			// swap asc's and desc's
			$order = str_ireplace('asc', 'thisisjustatemporarything', $order);
			$order = str_ireplace('desc', 'asc', $order);
			$order = str_ireplace('thisisjustatemporarything', 'desc', $order);

			$this->order($order);
		}

		return $this->first();
	}

	/**
	 * Returns the total elements that match the criteria.
	 *
	 * @param array|null $attributes
	 * @return int
	 */
	public function total($attributes = null)
	{
		$this->setAttributes($attributes);

		if (!isset($this->_totalCache))
		{
			$this->_totalCache = craft()->elements->getTotalElements($this);
		}

		return $this->_totalCache;
	}
}
