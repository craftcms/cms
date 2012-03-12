<?php
namespace Blocks;

/**
 *
 */
class ArrayTag extends VarTag
{
	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (isset($this->_var[$name]))
			return $this->_var[$name];
	}

	/**
	 * @param $name
	 * return mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return empty($this->_var) ? '' : '1';
	}

	/**
	 * @return mixed
	 */
	public function __toArray()
	{
		return $this->_var;
	}

	public function get($index)
	{
		if (isset($this->_var[$index]))
			return $this->_var[$index];

		return '';
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return count($this->_var);
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function join($glue = ', ')
	{
		return implode($glue, $this->_var);
	}
}
