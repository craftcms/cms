<?php
namespace Blocks;

/**
 * Form base class
 *
 * @abstract
 */
abstract class BaseForm extends \CFormModel
{
	protected $attributes = array();
	private $_attributes = array();

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
		if (array_key_exists($name, $this->attributes))
			$this->_attributes[$name] = $value;
		else
			throw new Exception(Blocks::t('“{className}” doesn’t have an attribute “{attributeName}”.', array('className' => get_class($this), 'attributeName' => $name)));
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
		if (array_key_exists($name, $this->attributes))
		{
			if (isset($this->_attributes[$name]))
				return $this->_attributes[$name];
			else
				return null;
		}
		else if ($name == 'errors')
			return $this->getErrors();
		else
			throw new Exception(Blocks::t('“{className}” doesn’t have an attribute “{attributeName}”.', array('className' => get_class($this), 'attributeName' => $name)));
	}

	/**
	 * Used by CActiveRecord
	 * 
	 * @return array Validation rules for model's attributes
	 */
	public function rules()
	{
		return ModelHelper::createRules($this->attributes);
	}
}
