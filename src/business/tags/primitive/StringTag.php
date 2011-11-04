<?php

class StringTag extends Tag
{
	public function uppercase()
	{
		$this->_val = strtoupper($this->_val);
		return $this;
	}

	public function lowercase()
	{
		$this->_val = strtolower($this->_val);
		return $this;
	}
}
