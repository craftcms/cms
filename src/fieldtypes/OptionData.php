<?php
namespace Craft;

/**
 * Class OptionData
 *
 * @package craft.app.fieldtypes
 */
class OptionData
{
	public $label;
	public $value;
	public $selected;

	/**
	 * Constructor
	 *
	 * @param string $label
	 * @param string $value
	 * @param        $selected
	 */
	function __construct($label, $value, $selected)
	{
		$this->label    = $label;
		$this->value    = $value;
		$this->selected = $selected;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->value;
	}
}
