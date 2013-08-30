<?php
namespace Craft;

/**
 * Model base class
 *
 * @abstract
 */
abstract class BaseModel extends \CModel
{
	private $_classHandle;
	private $_attributeConfigs;
	private $_attributeNames;
	private $_attributes;

	protected $classSuffix = 'Model';

	/**
	 * Constructor
	 *
	 * @param mixed $attributes
	 */
	function __construct($attributes = null)
	{
		ModelHelper::populateAttributeDefaults($this);
		$this->setAttributes($attributes);
	}

	/**
	 * PHP getter magic method.
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		if (in_array($name, $this->attributeNames()))
		{
			return $this->getAttribute($name);
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
	 * Magic __call() method, used for chain-setting attribute values.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return BaseModel
	 */
	function __call($name, $arguments)
	{
		if (in_array($name, $this->attributeNames()))
		{
			if (count($arguments) == 1)
			{
				$this->setAttribute($name, $arguments[0]);
			}
			else
			{
				$this->setAttribute($name, $arguments);
			}

			return $this;
		}
		else
		{
			return parent::__call($name, $arguments);
		}
	}

	/**
	 * Treats attributes defined by defineAttributes() as properties.
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (parent::__isset($name) || in_array($name, $this->attributeNames()))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Treats attributes defined by defineAttributes() as array offsets.
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		if (parent::offsetExists($offset) || in_array($offset, $this->attributeNames()))
		{
			return true;
		}
		else
		{
			return false;
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
	 * @access protected
	 * @return array
	 */
	abstract protected function defineAttributes();

	/**
	 * Returns this model's normalized attribute configs.
	 *
	 * @return array
	 */
	public function getAttributeConfigs()
	{
		if (!isset($this->_attributeConfigs))
		{
			$this->_attributeConfigs = array();

			foreach ($this->defineAttributes() as $name => $config)
			{
				$this->_attributeConfigs[$name] = ModelHelper::normalizeAttributeConfig($config);
			}
		}

		return $this->_attributeConfigs;
	}

	/**
	 * Returns the list of this model's attribute names.
	 *
	 * @return array
	 */
	public function attributeNames()
	{
		if (!isset($this->_attributeNames))
		{
			$this->_attributeNames = array_keys($this->getAttributeConfigs());
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

		foreach ($this->attributeNames() as $name)
		{
			if ($names === null || in_array($name, $names))
			{
				$values[$name] = $this->getAttribute($name, $flattenValues);
			}
		}

		return $values;
	}

	/**
	 * Gets an attribute's value.
	 *
	 * @param string $name
	 * @param bool $flattenValue
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		if (isset($this->_attributes[$name]))
		{
			if ($flattenValue)
			{
				return ModelHelper::packageAttributeValue($this->_attributes[$name]);
			}
			else
			{
				return $this->_attributes[$name];
			}
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
			$attributes = $this->getAttributeConfigs();
			$config = $attributes[$name];

			// Handle special case attribute types
			switch ($config['type'])
			{
				case AttributeType::DateTime:
				{
					if ($value)
					{
						if (!($value instanceof \DateTime))
						{
							if (DateTimeHelper::isValidTimeStamp($value))
							{
								$value = new DateTime('@'.$value);
							}
							else
							{
								$value = DateTime::createFromString($value);
							}
						}
					}
					else
					{
						// No empty strings allowed!
						$value = null;
					}

					break;
				}
				case AttributeType::Mixed:
				{
					if ($value && is_string($value) && strpos('{[', $value[0]) !== false)
					{
						// Presumably this is JSON.
						$value = JsonHelper::decode($value);
					}

					if ($config['model'])
					{
						$class = __NAMESPACE__.'\\'.$config['model'];
						$value = $class::populateModel($value);
					}

					break;
				}
			}

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
	 * @param mixed $values
	 */
	public function setAttributes($values)
	{
		if (is_array($values) || is_object($values))
		{
			foreach ($this->attributeNames() as $name)
			{
				// Make sure they're actually setting this attribute
				if (isset($values[$name]) || (is_array($values) && array_key_exists($name, $values)))
				{
					$this->setAttribute($name, $values[$name]);
				}
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
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		$class = get_called_class();
		return new $class($values);
	}

	/**
	 * Mass-populates models based on an array of attribute arrays.
	 *
	 * @param array $data
	 * @param string|null $indexBy
	 * @return array
	 */
	public static function populateModels($data, $indexBy = null)
	{
		$models = array();

		if (is_array($data))
		{
			foreach ($data as $values)
			{
				$model = static::populateModel($values);

				if ($indexBy)
				{
					$models[$model->$indexBy] = $model;
				}
				else
				{
					$models[] = $model;
				}
			}
		}

		return $models;
	}
}
