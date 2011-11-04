<?php

abstract class Tag
{
	protected $_val;

	function __construct($val)
	{
		$this->_val = $val;
	}

	public function __toString()
	{
		return $this->_val;
	}

	public function __toBool()
	{
		return (bool)$this->__toString();
	}

	public function __toTagArray()
	{
		$tags = array();

		if (is_array($this->_val))
		{
			foreach ($this->_val as $val)
			{
				$tagType = get_class($this);
				$tags[] = new $tagType($val);
			}
		}
		else
		{
			$tagType = get_class($this);
			$tags[] = new $tagType($this->_val);
		}

		return $tags;
	}
}
