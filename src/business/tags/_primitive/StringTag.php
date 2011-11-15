<?php

class StringTag extends Tag
{
	protected $_val;

	public function __construct($val = '')
	{
		$this->_val = (string)$val;
	}

	public function __toString()
	{
		return (string)$this->_val;
	}

	public function length()
	{
		return new NumTag(strlen($this->_val));
	}

	public function uppercase()
	{
		return new StringTag(strtoupper($this->_val));
	}

	public function lowercase()
	{
		return new StringTag(strtolower($this->_val));
	}

	public function chars()
	{
		$tags = array();

		if (strlen($this->_val))
		{
			$chars = str_split($this->_val);

			foreach ($chars as $char)
			{
				$tags[] = new StringTag($char);
			}
		}

		return new ArrayTag($tags);
	}

}
