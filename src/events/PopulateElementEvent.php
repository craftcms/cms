<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PopulateElementEvent extends ElementEvent
{
	// Properties
	// =========================================================================

	/**
	 * @var array The element query’s result for this element.
	 */
	public $row;
}
