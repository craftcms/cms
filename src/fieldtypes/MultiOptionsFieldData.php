<?php
namespace Craft;

/**
 * Multi-select option field data class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
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
