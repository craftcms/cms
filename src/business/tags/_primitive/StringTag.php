<?php

class StringTag extends Tag
{
	protected $_val;

	public function __construct($val = '')
	{
		$this->_val = (string)$val;
	}

	public function __toString()
	{
		return (string)$this->_val;
	}

	public function length()
	{
		return strlen($this->_val);
	}

	public function uppercase()
	{
		return strtoupper($this->_val);
	}

	public function lowercase()
	{
		return strtolower($this->_val);
	}

	public function chars()
	{
		if (strlen($this->_val))
		{
			return str_split($this->_val);
		}

		return array();
	}

}
