<?php

/**
 *
 */
class StringTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 *
	 * @param string $val
	 */
	protected function init($val = '')
	{
		$this->_val = (string)$val;
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->_val;
	}

	/**
	 * @access public
	 *
	 * @return int
	 */
	public function length()
	{
		return strlen($this->_val);
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function uppercase()
	{
		return strtoupper($this->_val);
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function lowercase()
	{
		return strtolower($this->_val);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function encode()
	{
		return BlocksHtml::encode($this->_val);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function decode()
	{
		return BlocksHtml::decode($this->_val);
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function chars()
	{
		if (strlen($this->_val))
		{
			return str_split($this->_val);
		}

		return array();
	}

}
