<?php

/**
 *
 */
class BoolTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 *
	 * @param bool $val
	 */
	protected function init($val = false)
	{
		$this->_val = $val;
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_val ? 'y': '';
	}

}
