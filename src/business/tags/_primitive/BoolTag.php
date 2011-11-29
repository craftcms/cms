<?php

class BoolTag extends Tag
{
	protected $_val;

	public function __construct($val = false)
	{
		$this->_val = $val;
	}

	public function __toString()
	{
		return $this->_val ? 'y': '';
	}

}
