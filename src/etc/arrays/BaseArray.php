<?php
namespace Craft;

/**
 * Base array class
 */
abstract class BaseArray implements \ArrayAccess, \IteratorAggregate
{
	/**
	 * @var array The array this class pretends to be.
	 * @access protected
	 */
	protected $values;

	/**
	 * Constructor
	 *
	 * @param array|null $values
	 */
	function __construct($values = null)
	{
		if (is_array($values))
		{
			$this->values = $values;
		}
		else
		{
			$this->values = array();
		}
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->values[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @throws Exception
	 */
	public function offsetGet($offset)
	{
		if (isset($this->values[$offset]))
		{
			return $this->values[$offset];
		}
		else
		{
			throw new Exception(Craft::t('Property "{class}.{property}" is not defined.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
		}
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->values[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
	}

	/**
	 * @return array
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->values);
	}
}
