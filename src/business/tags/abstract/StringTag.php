<?php

class StringTag
{
	protected $_val;

	function __construct($val)
	{
		$this->_val = $val;
	}

	public function __toString()
	{
		return $this->_val;
	}
}
