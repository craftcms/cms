<?php
namespace Blocks;

/**
 *
 */
class DateTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 * @param null $val
	 */
	protected function init($val = null)
	{
		if ($val)
			$this->_val = $this->getUnixTimestamp($val);
		else
			$this->_val = time();
	}

	/**
	 * @access protected
	 * @param null $val
	 * @return int
	 */
	protected function getUnixTimestamp($val = null)
	{
		if ($val)
		{
			// was a Unix timestamp passed?
			if (preg_match('/\d{10}/', (string)$val))
				return (int)$val;
		}

		// just set to the current time
		return time();
	}

	/**
	 * @return string
	 */
	public function year()
	{
		return date('Y', $this->_val);
	}

	/**
	 * @return string
	 */
	public function month()
	{
		return date('n', $this->_val);
	}

	/**
	 * @return string
	 */
	public function day()
	{
		return date('j', $this->_val);
	}
}
