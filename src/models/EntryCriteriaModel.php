<?php
namespace Blocks;

/**
 * Entry criteria model class
 */
class EntryCriteriaModel extends BaseModel
{
	private $_entryType;

	/**
	 * Constructor
	 *
	 * @param mixed $attributes
	 * @param BaseEntryType $entryType
	 */
	function __construct($attributes, BaseEntryType $entryType)
	{
		$this->_entryType = $entryType;
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
			'after'         => AttributeType::DateTime,
			'before'        => AttributeType::DateTime,
			'uri'           => AttributeType::String,
			'status'        => array(AttributeType::Enum, 'values' => array('live', 'pending', 'expired', 'disabled'), 'default' => 'live'),
			'archived'      => AttributeType::Bool,
			'order'         => array(AttributeType::String, 'default' => 'postDate desc'),
			'offset'        => array(AttributeType::Number, 'default' => 0),
			'limit'         => array(AttributeType::Number, 'default' => 100),
			'indexBy'       => AttributeType::String,

			//'title'         => AttributeType::String,
			//'dateCreated'   => AttributeType::DateTime,
			//'dateUpdated'   => AttributeType::DateTime,
		);

		// Mix in any custom attributes defined by the entry type
		$entryTypeAttributes = $this->_entryType->defineCustomCriteriaAttributes();
		$attributes = array_merge($attributes, $entryTypeAttributes);

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
	 * Returns the entry type.
	 *
	 * @return BaseEntryType
	 */
	public function getEntryType()
	{
		return $this->_entryType;
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
	 * Returns all entries that match the criteria.
	 *
	 * @param array|null $attributes
	 * @return array
	 */
	public function find($attributes = null)
	{
		$this->setAttributes($attributes);
		return blx()->entries->findEntries($this);
	}

	/**
	 * Returns the first entry that matches the criteria.
	 *
	 * @param array|null $attributes
	 * @return EntryModel|null
	 */
	public function first($attributes = null)
	{
		$this->setAttributes($attributes);
		return blx()->entries->findEntry($this);
	}

	/**
	 * Returns the total entries that match the criteria.
	 *
	 * @param array|null $attributes
	 * @return int
	 */
	public function total($attributes = null)
	{
		$this->setAttributes($attributes);
		return blx()->entries->getTotalEntries($this);
	}
}
