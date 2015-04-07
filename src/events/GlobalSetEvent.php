<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\GlobalSet;

/**
 * Global Set event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalSetEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var GlobalSet The global set model associated with the event.
	 */
	public $globalSet;
}
