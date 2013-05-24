<?php
namespace Craft;

/**
 * Multi-select option field data class
 */
class MultiOptionsFieldData implements \ArrayAccess, \IteratorAggregate
{
	public $options;
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

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function contains($value)
	{
		$value = (string) $value;

		foreach ($this->values as $selectedValue)
		{
			if ($value == $selectedValue->value)
			{
				return true;
			}
		}

		return false;
	}
}
