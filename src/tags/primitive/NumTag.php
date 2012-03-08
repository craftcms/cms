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

	/**
	 * @param $num
	 * @return mixed
	 */
	public function plus($num)
	{
		return $this->_val + $num;
	}

	/**
	 * @param $num
	 * @return mixed
	 */
	public function minus($num)
	{
		return $this->_val - $num;
	}

	/**
	 * @param $num
	 * @return float
	 */
	public function dividedBy($num)
	{
		return $this->_val / $num;
	}

	/**
	 * @param $num
	 * @return mixed
	 */
	public function times($num)
	{
		return $this->_val * $num;
	}

	/**
	 * @return string
	 */
	public function toHumanTimeDuration()
	{
		return DateTimeHelper::secondsToHumanTimeDuration($this->_val);
	}

	/**
	 * @return string
	 */
	protected function toWord()
	{
		return NumberHelper::word($this->_val);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function formatDate($format = 'MM-dd-yyyy HH:mm:ss')
	{
		return b()->dateFormatter->format($format, $this->_val);
	}

	//public function round() {}
	//public function format() {}

}
