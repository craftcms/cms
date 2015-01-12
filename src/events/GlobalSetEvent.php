<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

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
	 * @var \craft\app\models\GlobalSet The global set model associated with the event.
	 */
	public $globalSet;
}
