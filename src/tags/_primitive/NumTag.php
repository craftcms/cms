<?php

class NumTag extends Tag
{
	protected $_val;

	protected function init($val = 0)
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
