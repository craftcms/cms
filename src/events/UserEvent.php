<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;

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
	 * @var User The user model associated with the event.
	 */
	public $user;
}
