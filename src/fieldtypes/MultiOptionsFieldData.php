<?php
namespace Craft;

/**
 * Multi-select option field data class
 */
class MultiOptionsFieldData extends \ArrayObject
{
	public $options;

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
