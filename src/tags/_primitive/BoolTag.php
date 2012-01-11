<?php

class BoolTag extends Tag
{
	protected $_val;

	protected function init($val = false)
	{
		$this->_val = $val;
	}

	public function __toString()
	{
		return $this->_val ? 'y': '';
	}

}
