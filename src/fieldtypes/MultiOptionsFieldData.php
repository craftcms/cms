<?php
namespace Craft;

/**
 * Multi-select option field data class.
 *
 * @package craft.app.fieldtypes
 */
class MultiOptionsFieldData extends \ArrayObject
{
	private $_options;

	/**
	 * Returns the options.
	 *
	 * @return array|null
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Sets the options.
	 *
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->_options = $options;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function contains($value)
	{
		$value = (string) $value;

		foreach ($this as $selectedValue)
		{
			if ($value == $selectedValue->value)
			{
				return true;
			}
		}

		return false;
	}
}
