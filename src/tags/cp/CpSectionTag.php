<?php

class CpSectionTag extends Tag
{
	private $_handle = null;
	private $_name = null;

	function __construct($handle, $name)
	{
		$this->_handle = $handle;
		$this->_name = $name;
	}

	public function __toString()
	{
		return $this->name();
	}

	public function handle()
	{
		return $this->_handle;
	}

	public function name()
	{
		return $this->_name;
	}
}
