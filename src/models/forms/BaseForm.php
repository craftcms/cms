<?php
namespace Blocks;

/**
 * Form base class
 * @abstract
 */
abstract class BaseForm extends \CFormModel
{
	protected $attributes = array();
	private $_attributes = array();

	/**
	 * Attribute Setter
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed|void
	 * @throws Exception
	 */
	function __set($name, $value)
	{
		if (array_key_exists($name, $this->attributes))
			$this->_attributes[$name] = $value;
		else
			throw new Exception(get_class($this).' doesn’t have an attribute “'.$name.'”.');
	}

	/**
	 * Attribute Setter
	 * @param string $name
	 * @throws Exception
	 * @return mixed|null
	 */
	function __get($name)
	{
		if (array_key_exists($name, $this->attributes))
		{
			if (isset($this->_attributes[$name]))
				return $this->_attributes[$name];
			else
				return null;
		}
		else
			throw new Exception(get_class($this).' doesn’t have an attribute “'.$name.'”.');
	}

	/**
	 * Used by CActiveRecord
	 * @return array Validation rules for model's attributes
	 */
	public function rules()
	{
		return ModelHelper::createRules($this->attributes);
	}
}
