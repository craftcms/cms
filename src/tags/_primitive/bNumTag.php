<?php

/**
 *
 */
class bNumTag extends bTag
{
	protected $_val;

	/**
	 * @access protected
	 * @param int $val
	 */
	protected function init($val = 0)
	{
		$this->_val = is_numeric($val) ? $val : 0;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->_val;
	}

	//public function round() {}
	//public function format() {}

}
