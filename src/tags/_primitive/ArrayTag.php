<?php

/**
 *
 */
class ArrayTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 *
	 * @param array $val
	 */
	protected function init($val = array())
	{
		$this->_val = is_array($val) ? $val : array();
	}

	/**
	 * @access public
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return Tag
	 */
	public function __call($name, $args)
	{
		if (isset($this->_val[$name]))
		{
			return $this->_val[$name];
		}

		return parent::__call($name, $args);
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->_val)
			return '';

		$strings = array();

		foreach ($this->_val as $val)
		{
			if (is_object($val))
			{
				$strings[] = $val->__toString();
			}
			else
			{
				$strings[] = (string)$val;
			}
		}

		return '['.implode(',', $strings).']';
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function __toArray()
	{
		return $this->_val;
	}

	/**
	 * @access public
	 *
	 * @return int
	 */
	public function length()
	{
		return count($this->_val);
	}
}
