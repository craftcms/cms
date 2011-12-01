<?php

class ArrayTag extends Tag
{
	protected $_val;
	protected $_tagified = false;

	public function __construct($val = array())
	{
		$this->_val = is_array($val) ? $val : array();
	}

	public function __call($name, $args)
	{
		if (isset($this->_val[$name]))
		{
			return self::_getVarTag($this->_val[$name]);
		}

		return parent::__call($name, $args);
	}

	/**
	 * Makes sure that each element of the array is a tag
	 */
	protected function _tagify()
	{
		if (!$this->_tagified)
		{
			foreach ($this->_val as &$tag)
			{
				$tag = self::_getVarTag($tag);
			}
			$this->_tagified = true;
		}
	}

	public function __toString()
	{
		if (!$this->_val)
			return '';

		$strings = array();

		foreach ($this->_val as $val)
		{
			if (is_object($val))
			{
				$strings[] = $val->__toString();
			}
			else
			{
				$strings[] = (string)$val;
			}
		}

		return '['.implode(',', $strings).']';
	}

	public function __toArray()
	{
		$this->_tagify();
		return $this->_val;
	}

	public function length()
	{
		return count($this->_val);
	}
}
