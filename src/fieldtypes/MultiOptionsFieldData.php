<?php
namespace Craft;

/**
 * Multi-select option field data class
 */
class MultiOptionsFieldData extends BaseArray
{
	public $options;

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
