<?php

class NumTag extends Tag
{
	protected $_val;

	public function __construct($val)
	{
		$this->_val = is_numeric($val) ? $val : 0;
	}

	public function __toString()
	{
		return (string)$this->_val;
	}

	//public function round() {}
	//public function format() {}

}
