<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields\data;

/**
 * Multi-select option field data class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MultiOptionsFieldData extends \ArrayObject
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_options;

	// Public Methods
	// =========================================================================

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
	 *
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
