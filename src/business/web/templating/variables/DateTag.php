<?php
namespace Blocks;

/**
 *
 */
class DateTag extends VarTag
{
	/**
	 * Constructor
	 * @param mixed $var
	 */
	public function __construct($var = null)
	{
		$this->_var = $this->getUnixTimestamp($var);
	}

	/**
	 * @access protected
	 * @param null $var
	 * @return int
	 */
	protected function getUnixTimestamp($var = null)
	{
		if ($var)
		{
			// was a Unix timestamp passed?
			if (preg_match('/\d{10}/', (string)$var))
				return (int)$var;
		}

		// just set to the current time
		return time();
	}

	/**
	 * @return string
	 */
	public function year()
	{
		return date('Y', $this->_var);
	}

	/**
	 * @return string
	 */
	public function month()
	{
		return date('n', $this->_var);
	}

	/**
	 * @return string
	 */
	public function day()
	{
		return date('j', $this->_var);
	}
}
