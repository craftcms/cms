<?php
namespace Blocks;

/**
 * Model base class
 *
 * @abstract
 */
abstract class BaseModel extends \CModel
{
	private $_classHandle;
	private $_attributeNames = array();
	private $_attributes = array();

	protected $classSuffix = 'Model';

	/**
	 * Constructor
	 */
	function __construct()
	{
		ModelHelper::populateAttributeDefaults($this);
	}

	/**
	 * PHP getter magic method.
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		if (isset($this->_attributes[$name]))
		{
			return $this->_attributes[$name];
		}
		else if (in_array($name, $this->attributeNames()))
		{
			return null;
		}
		else
		{
			return parent::__get($name);
		}
	}

	/**
	 * PHP setter magic method.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed
	 */
	function __set($name, $value)
	{
		if ($this->setAttribute($name, $value) === false)
		{
			parent::__set($name, $value);
		}
	}

	/**
	 * Checks if an attribute value is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (isset($this->_attributes[$name]))
		{
			return true;
		}
		// We're mostly just concerned with whether the attribute exists,
		// so even not-yet-set attributes should return 'true' here.
		else if (in_array($name, $this->attributeNames()))
		{
			return true;
		}
		else
		{
			return parent::__isset($name);
		}
	}

	/**
	 * Get the class name, sans namespace and suffix.
	 *
	 * @return string
	 */
	public function getClassHandle()
	{
		if (!isset($this->_classHandle))
		{
			// Chop off the namespace
			$classHandle = substr(get_class($this), strlen(__NAMESPACE__) + 1);

			// Chop off the class suffix
			$suffixLength = strlen($this->classSuffix);

			if (substr($classHandle, -$suffixLength) == $this->classSuffix)
			{
				$classHandle = substr($classHandle, 0, -$suffixLength);
			}

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}

	/**
	 * Defines this model's attributes.
	 *
	 * @abstract
	 * @return array
	 */
	abstract public function defineAttributes();

	/**
	 * Returns the list of this model's attribute names.
	 *
	 * @return array
	 */
	public function attributeNames()
	{
		if (!$this->_attributeNames)
		{
			$this->_attributeNames = array_keys($this->defineAttributes());
		}

		return $this->_attributeNames;
	}

	/**
	 * Returns an array of attribute values.
	 *
	 * @param null $names
	 * @param bool $flattenValues Will change a DateTime object to a timestamp, Mixed to array, etc. Useful for saving to DB or sending over a web service.
	 *
	 * @return array
	 */
	public function getAttributes($names = null, $flattenValues = false)
	{
		$values = array();

		foreach ($this->defineAttributes() as $name => $config)
		{
			if (!is_array($names) || in_array($name, $names))
			{
				if ($flattenValues)
				{
					$values[$name] = ModelHelper::packageAttributeValue($config, $this->getAttribute($name));
				}
				else
				{
					$values[$name] = $this->getAttribute($name);
				}
			}
		}

		return $values;
	}

	/**
	 * Gets an attribute's value.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		if (isset($this->_attributes[$name]))
		{
			return $this->_attributes[$name];
		}
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
		if (in_array($name, $this->attributeNames()))
		{
			$this->_attributes[$name] = $value;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Sets multiple attribute values at once.
	 *
	 * @param array $values
	 */
	public function setAttributes($values)
	{
		if (!is_array($values))
		{
			return;
		}

		foreach ($values as $name => $value)
		{
			if (in_array($name, $this->attributeNames()))
			{
				$this->_attributes[$name] = $value;
			}
		}
	}

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		return ModelHelper::getRules($this);
	}

	/**
	 * Returns the attribute labels.
	 *
	 * @return array
	 */
	public function attributeLabels()
	{
		return ModelHelper::getAttributeLabels($this);
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $attributes
	 * @return BaseModel
	 */
	public static function populateModel($attributes)
	{
		$class = get_called_class();
		$model = new $class;

		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		foreach ($model->defineAttributes() as $name => $config)
		{
			if (isset($attributes[$name]))
			{
				$value = $attributes[$name];
				$config = ModelHelper::normalizeAttributeConfig($config);

				if ($config['type'] == AttributeType::DateTime && is_numeric($value))
				{
					$value = new DateTime('@'.$value);
				}

				$model->setAttribute($name, $value);
			}
		}

		return $model;
	}

	/**
	 * Mass-populates models based on an array of attribute arrays.
	 *
	 * @param array $data
	 * @param string|null $index
	 * @return array
	 */
	public static function populateModels($data, $index = null)
	{
		$models = array();

		foreach ($data as $attributes)
		{
			$model = static::populateModel($attributes);

			if ($index === null)
			{
				$models[] = $model;
			}
			else
			{
				$models[$model->$index] = $model;
			}
		}

		return $models;
	}
}
