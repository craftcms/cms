<?php

/**
 *
 */
class CpSectionTag extends Tag
{
	private $_handle = null;
	private $_name = null;

	/**
	 * @param $handle
	 * @param $name
	 */
	function __construct($handle, $name)
	{
		$this->_handle = $handle;
		$this->_name = $name;
	}

	/**
	 * @return null
	 */
	public function __toString()
	{
		return $this->name();
	}

	/**
	 * @return null
	 */
	public function handle()
	{
		return $this->_handle;
	}

	/**
	 * @return null
	 */
	public function name()
	{
		return $this->_name;
	}
}
