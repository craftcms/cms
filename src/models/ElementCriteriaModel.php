<?php
namespace Craft;

/**
 * Element criteria model class
 */
class ElementCriteriaModel extends BaseModel
{
	private $_elementType;

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
			'id'            => AttributeType::Number,
			'ref'           => AttributeType::String,
			'locale'        => AttributeType::Locale,
			'uri'           => AttributeType::String,
			'status'        => array(AttributeType::String, 'default' => BaseElementModel::ENABLED),
			'archived'      => AttributeType::Bool,
			'order'         => array(AttributeType::String, 'default' => 'dateCreated desc'),
			'offset'        => array(AttributeType::Number, 'default' => 0),
			'limit'         => array(AttributeType::Number, 'default' => 100),
			'indexBy'       => AttributeType::String,
			'search'        => AttributeType::String,
			'dateCreated'   => AttributeType::Mixed,
			'dateUpdated'   => AttributeType::Mixed,
			'parentOf'      => AttributeType::Mixed,
			'parentField'   => AttributeType::String,
			'childOf'       => AttributeType::Mixed,
			'childField'    => AttributeType::String,
		);

		// Mix in any custom attributes defined by the element type
		$elementTypeAttributes = $this->_elementType->defineCriteriaAttributes();
		$attributes = array_merge($attributes, $elementTypeAttributes);

		// Mix in the custom fields
		$fields = craft()->fields->getAllFields();

		foreach ($fields as $field)
		{
			$attributes[$field->handle] = AttributeType::Mixed;
		}

		return $attributes;
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
		return craft()->elements->findElements($this, true);
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
		return craft()->elements->getTotalElements($this);
	}
}
