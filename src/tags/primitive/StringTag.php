<?php
namespace Blocks;

/**
 *
 */
class StringTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 * @param string $val
	 */
	protected function init($val = '')
	{
		$this->_val = (string)$val;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->_val;
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return strlen($this->_val);
	}

	/**
	 * @return string
	 */
	public function uppercase()
	{
		return strtoupper($this->_val);
	}

	/**
	 * @return string
	 */
	public function lowercase()
	{
		return strtolower($this->_val);
	}

	/**
	 * @return mixed
	 */
	public function encode()
	{
		return HtmlHelper::encode($this->_val);
	}

	/**
	 * @return mixed
	 */
	public function decode()
	{
		return HtmlHelper::decode($this->_val);
	}

	/**
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

	/**
	 * Split
	 */
	public function split($delimiter = ',')
	{
		return array_map('trim', explode($delimiter, $this->_val));
	}

}
