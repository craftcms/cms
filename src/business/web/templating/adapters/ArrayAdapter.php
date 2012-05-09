<?php
namespace Blocks;

/**
 *
 */
class ArrayAdapter extends Adapter
{
	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	function __call($name, $args)
	{
		if (isset($this->_var[$name]))
			return $this->_var[$name];
	}

	/**
	 * @param $name
	 * return mixed
	 * @return string
	 */
	function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return empty($this->_var) ? '' : '1';
	}

	/**
	 * @return mixed
	 */
	function __toArray()
	{
		return $this->_var;
	}

	/**
	 * Returns whether an element is in the array
	 * @param mixed $elem
	 * @return bool
	 */
	public function includes($elem)
	{
		return in_array($elem, $this->_var);
	}

	/**
	 * Returns the element at a specific index.
	 * @param mixed $index
	 * @return mixed
	 */
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
	 * Adds an element(s) to the end of the array
	 */
	public function push()
	{
		$args = func_get_args();
		$this->_var = array_merge($this->_var, $args);
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
