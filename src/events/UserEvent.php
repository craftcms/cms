<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\User The user model associated with the event.
	 */
	public $user;
}
