<?php
namespace Blocks;

/**
 *
 */
class NumTag extends VarTag
{
	/**
	 * @param $num
	 * @return mixed
	 */
	public function plus($num)
	{
		return $this->_var + $num;
	}

	/**
	 * @param $num
	 * @return mixed
	 */
	public function minus($num)
	{
		return $this->_var - $num;
	}

	/**
	 * @param $num
	 * @return float
	 */
	public function dividedBy($num)
	{
		return $this->_var / $num;
	}

	/**
	 * @param $num
	 * @return mixed
	 */
	public function times($num)
	{
		return $this->_var * $num;
	}

	/**
	 * @return string
	 */
	public function toHumanTimeDuration()
	{
		return DateTimeHelper::secondsToHumanTimeDuration($this->_var);
	}

	/**
	 * @return string
	 */
	protected function toWord()
	{
		return NumberHelper::word($this->_var);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function formatDate($format = 'MM-dd-yyyy HH:mm:ss')
	{
		return b()->dateFormatter->format($format, $this->_var);
	}

	//public function round() {}
	//public function format() {}

}
