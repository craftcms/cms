<?php
namespace Blocks;

/**
 * Model base class
 *
 * @abstract
 */
abstract class BaseModel extends \CModel
{
	private $_values = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		ModelHelper::populateAttributeDefaults($this);
	}

	/**
	 * Defines this model's attributes.
	 *
	 * @abstract
	 * @return array
	 */
	abstract public function defineAttributes();

	/**
	 * Isset?
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
	{
		return array_key_exists($name, $this->defineAttributes());
	}

	/**
	 * Attribute Setter
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed|void
	 * @throws Exception
	 */
	function __set($name, $value)
	{
		if (array_key_exists($name, $this->defineAttributes()))
			$this->_values[$name] = $value;
		else
			$this->_noAttributeExists($name);
	}

	/**
	 * Attribute Getter
	 *
	 * @param string $name
	 * @throws Exception
	 * @return mixed|null
	 */
	function __get($name)
	{
		if (array_key_exists($name, $this->defineAttributes()))
		{
			if (isset($this->_values[$name]))
				return $this->_values[$name];
			else
				return null;
		}
		else if ($name == 'errors')
			return $this->getErrors();
		else
			$this->_noAttributeExists($name);
	}

	/**
	 * Throws a "no attribute exists" exception
	 *
	 * @param string $attribute
	 * @throws Exception
	 */
	private function _noAttributeExists($attribute)
	{
		throw new Exception(Blocks::t('“{class}” doesn’t have an attribute called “{attribute}”.', array('class' => get_class($this), 'attribute' => $attribute)));
	}

	/**
	 * Returns the list of this model's attribute names.
	 *
	 * @return array
	 */
	public function attributeNames()
	{
		return array_keys($this->defineAttributes());
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
}
