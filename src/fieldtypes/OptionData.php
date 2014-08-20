<?php
namespace Craft;

/**
 * Class OptionData
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class OptionData
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var string
	 */
	public $value;

	/**
	 * @var
	 */
	public $selected;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $label
	 * @param string $value
	 * @param        $selected
	 *
	 * @return OptionData
	 */
	public function __construct($label, $value, $selected)
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
