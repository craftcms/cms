<?php

class DateTag extends Tag
{
	protected $_val;

	protected function init($val = null)
	{
		$this->_val = $this->getUnixTimestamp($val);
	}

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

	public function year()
	{
		return date('Y', $this->_val);
	}

	public function month()
	{
		return date('n', $this->_val);
	}

	public function day()
	{
		return date('j', $this->_val);
	}
}
