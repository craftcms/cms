<?php
namespace Craft;

/**
 * Single-select option field data class
 */
class SingleOptionFieldData extends OptionData
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
}
