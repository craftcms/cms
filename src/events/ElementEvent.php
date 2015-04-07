<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\base\ElementInterface;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementInterface The element model associated with the event.
	 */
	public $element;
}
