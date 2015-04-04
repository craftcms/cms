<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * InvalidComponentTrait implements the common methods and properties for invalid component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait InvalidComponentTrait
{
	// Static
	// =========================================================================

	// Properties
	// =========================================================================

	/**
	 * @var string|Component The expected component class name.
	 */
	public $type;

	/**
	 * @var string The exception message that explains why the component class was invalid
	 */
	public $errorMessage;

	/**
	 * @var mixed The custom settings associated with the component, if it is savable
	 */
	public $settings;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the expected component class name.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
}
