<?php

/**
 *
 */
class ObjectTag extends Tag
{
	protected $_obj;

	/**
	 * @access protected
	 * @param null $obj
	 */
	protected function init($obj = null)
	{
		$this->_obj = is_object($obj) ? $obj : new stdClass;
	}

	/**
	 * @param $name
	 * @param $args
	 * @return mixed|Tag
	 */
	public function __call($name, $args)
	{
		if (method_exists($this->_obj, $name))
		{
			return call_user_func_array(array($this->_obj, $name), $args);
		}

		if (isset($this->_obj->$name))
		{
			return $this->_obj->$name;
		}

		return parent::__call($name, $args);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		if (method_exists($this->_obj, '__toString'))
			return $this->_obj->__toString();

		return get_class($this->_obj);
	}

	/**
	 * @return mixed
	 */
	public function __toArray()
	{
		return $this->_obj;
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return count($this->_obj);
	}
}
