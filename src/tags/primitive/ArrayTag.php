<?php
namespace Blocks;

/**
 *
 */
class ArrayTag extends Tag
{
	protected $_val;

	/**
	 * @access protected
	 * @param array $val
	 */
	protected function init($val = array())
	{
		$this->_val = is_array($val) ? $val : array();
	}

	/**
	 * @param $name
	 * @param $args
	 * @return Tag
	 */
	public function __call($name, $args)
	{
		if (isset($this->_val[$name]))
		{
			return $this->_val[$name];
		}

		return parent::__call($name, $args);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return empty($this->_val) ? '' : '1';
		if (!$this->_val)
			return '';

		$strings = array();

		foreach ($this->_val as $val)
		{
			if (is_object($val) && method_exists($val, '__toString'))
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

	/**
	 * @return mixed
	 */
	public function __toArray()
	{
		return $this->_val;
	}

	public function get($index)
	{
		if (isset($this->_val[$index]))
			return $this->_val[$index];

		return '';
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return count($this->_val);
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function join($glue = ', ')
	{
		return implode($glue, $this->_val);
	}
}
