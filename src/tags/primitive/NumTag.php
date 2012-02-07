<?php
namespace Blocks;

/**
 *
 */
class NumTag extends Tag
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

	public function plus($num)
	{
		return $this->_val + $num;
	}

	public function minus($num)
	{
		return $this->_val - $num;
	}

	public function dividedBy($num)
	{
		return $this->_val / $num;
	}

	public function times($num)
	{
		return $this->_val * $num;
	}

	//public function round() {}
	//public function format() {}

}
