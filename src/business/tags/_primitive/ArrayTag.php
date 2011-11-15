<?php

class ArrayTag extends Tag
{
	protected $_val;

	public function __construct($val = array())
	{
		$this->_val = is_array($val) ? $val : array();
	}

	public function __toString()
	{
		$strings = array();

		foreach ($this->_val as $val)
		{
			if (is_object($val) && method_exists($val, '__toString'))
			{
				$strings[] = $val->__toString();
			}
			else
			{
				$strings[] = 'NaT';
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
