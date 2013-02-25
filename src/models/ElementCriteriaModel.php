<?php
namespace Blocks;

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
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes = array(
			'id'            => AttributeType::Number,
			'locale'        => AttributeType::Locale,
			'uri'           => AttributeType::String,
			'status'        => AttributeType::String,
			'archived'      => AttributeType::Bool,
			'order'         => array(AttributeType::String, 'default' => 'dateCreated desc'),
			'offset'        => array(AttributeType::Number, 'default' => 0),
			'limit'         => array(AttributeType::Number, 'default' => 100),
			'indexBy'       => AttributeType::String,
			//'dateCreated'   => AttributeType::DateTime,
			//'dateUpdated'   => AttributeType::DateTime,
		);

		// Mix in any custom attributes defined by the element type
		$elementTypeAttributes = $this->_elementType->defineCustomCriteriaAttributes();
		$attributes = array_merge($attributes, $elementTypeAttributes);

		// Mix in the custom fields
		$fields = blx()->fields->getAllFields();

		foreach ($fields as $field)
		{
			$attributes[$field->handle] = AttributeType::Mixed;
		}

		return $attributes;
	}

	/**
	 * Sets an attribute's value.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		// Treat asterisks as null (for fun)
		if ($value === '*')
		{
			$value = null;
		}

		return parent::setAttribute($name, $value);
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
		return blx()->elements->findElements($this);
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
		return blx()->elements->findElement($this);
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
		return blx()->elements->getTotalElements($this);
	}
}
