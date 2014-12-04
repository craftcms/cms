<?php
namespace Craft;

/**
 * Model base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
abstract class BaseModel extends \CModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $classSuffix = 'Model';

	/**
	 * @var bool Whether this model should be strict about only allowing values to be set on defined attributes
	 */
	protected $strictAttributes = true;

	/**
	 * @var
	 */
	private $_classHandle;

	/**
	 * @var
	 */
	private $_attributeConfigs;

	/**
	 * @var
	 */
	private $_attributes;

	/**
	 * @var
	 */
	private $_extraAttributeNames;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param mixed $attributes
	 *
	 * @return BaseModel
	 */
	public function __construct($attributes = null)
	{
		if (!$this->strictAttributes)
		{
			$this->_extraAttributeNames = array();
		}

		ModelHelper::populateAttributeDefaults($this);
		$this->setAttributes($attributes);

		$this->attachBehaviors($this->behaviors());
	}

	/**
	 * PHP getter magic method.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
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
	 *
	 * @return mixed
	 */
	public function __set($name, $value)
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
	 * @param array  $arguments
	 *
	 * @return BaseModel
	 */
	public function __call($name, $arguments)
	{
		try
		{
			return parent::__call($name, $arguments);
		}
		catch (\CException $e)
		{
			// Is this one of our attributes?
			if (!$this->strictAttributes || in_array($name, $this->attributeNames()))
			{
				$copy = $this->copy();

				if (count($arguments) == 1)
				{
					$copy->setAttribute($name, $arguments[0]);
				}
				else
				{
					$copy->setAttribute($name, $arguments);
				}

				return $copy;
			}

			throw $e;
		}
	}

	/**
	 * Treats attributes defined by defineAttributes() as properties.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
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
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @param mixed $values
	 *
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
	 * @param array       $data
	 * @param string|null $indexBy
	 *
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

	/**
	 * Treats attributes defined by defineAttributes() as array offsets.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
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
			$classHandle = mb_substr(get_class($this), mb_strlen(__NAMESPACE__) + 1);

			// Chop off the class suffix
			$suffixLength = mb_strlen($this->classSuffix);

			if (mb_substr($classHandle, -$suffixLength) == $this->classSuffix)
			{
				$classHandle = mb_substr($classHandle, 0, -$suffixLength);
			}

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}

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
		$attributeNames = array_keys($this->getAttributeConfigs());

		if (!$this->strictAttributes)
		{
			$attributeNames = array_merge($attributeNames, $this->_extraAttributeNames);
		}

		return $attributeNames;
	}

	/**
	 * Returns a list of the names of the extra attributes that have been saved on this model, if it's not strict.
	 *
	 * @return array
	 */
	public function getExtraAttributeNames()
	{
		return $this->_extraAttributeNames;
	}

	/**
	 * Returns an array of attribute values.
	 *
	 * @param null $names
	 * @param bool $flattenValues Will change a DateTime object to a timestamp, Mixed to array, etc. Useful for saving
	 *                            to DB or sending over a web service.
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
	 * Gets an attribute’s value.
	 *
	 * @param string $name         The attribute’s name.
	 * @param bool   $flattenValue
	 *
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
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		if (!$this->strictAttributes || in_array($name, $this->attributeNames()))
		{
			// Is this a normal attribute?
			if (array_key_exists($name, $this->_attributeConfigs))
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
						if ($value && is_string($value) && mb_strpos('{[', $value[0]) !== false)
						{
							// Presumably this is JSON.
							$value = JsonHelper::decode($value);
						}

						if (is_array($value))
						{
							if ($config['model'])
							{
								$class = __NAMESPACE__.'\\'.$config['model'];
								$value = $class::populateModel($value);
							}
							else
							{
								$value = ModelHelper::expandModelsInArray($value);
							}
						}

						break;
					}
				}
			}
			// Is this the first time this extra attribute has been set?
			else if (!array_key_exists($name, $this->_extraAttributeNames))
			{
				$this->_extraAttributeNames[] = $name;
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
	 *
	 * @return null
	 */
	public function setAttributes($values)
	{
		// If this is a model, get the actual attributes on it
		if ($values instanceof \CModel)
		{
			$model = $values;
			$values = $model->getAttributes();

			// Is this a record?
			if ($model instanceof \CActiveRecord)
			{
				// Set any eager-loaded relations' values
				foreach (array_keys($model->getMetaData()->relations) as $name)
				{
					if ($model->hasRelated($name))
					{
						$this->setAttribute($name, $model->$name);
					}
				}
			}
		}

		if (is_array($values) || $values instanceof \Traversable)
		{
			foreach ($values as $name => $value)
			{
				$this->setAttribute($name, $value);
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
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/runtime/logs` folder with a level of LogLevel::Warning.
	 *
	 * @param null $attributes
	 * @param bool $clearErrors
	 *
	 * @return bool
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		if (parent::validate($attributes, $clearErrors))
		{
			return true;
		}

		foreach ($this->getErrors() as $attribute => $errorMessages)
		{
			foreach ($errorMessages as $errorMessage)
			{
				Craft::log(get_class($this).'->'.$attribute.' failed validation: '.$errorMessage, LogLevel::Warning);
			}
		}

		return false;
	}

	/**
	 * Returns all errors in a single list.
	 *
	 * @return array
	 */
	public function getAllErrors()
	{
		$errors = array();

		foreach ($this->getErrors() as $attributeErrors)
		{
			$errors = array_merge($errors, $attributeErrors);
		}

		return $errors;
	}

	/**
	 * Returns a copy of this model.
	 *
	 * @return BaseModel
	 */
	public function copy()
	{
		$class = get_class($this);
		return new $class($this->getAttributes());
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array();
	}
}
