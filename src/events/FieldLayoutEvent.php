<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\FieldLayout The field layout associated with this event.
	 */
	public $layout;
}
