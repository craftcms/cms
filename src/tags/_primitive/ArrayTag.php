<?php

class ArrayTag extends Tag
{
	protected $_val;

	protected function init($val = array())
	{
		$this->_val = is_array($val) ? $val : array();
	}

	public function __call($name, $args)
	{
		if (isset($this->_val[$name]))
		{
			return $this->_val[$name];
		}

		return parent::__call($name, $args);
	}

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

	public function __toArray()
	{
		return $this->_val;
	}

	public function length()
	{
		return count($this->_val);
	}
}
