<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;
use craft\app\models\Email;

/**
 * Email event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var User The user receiving the email
	 */
	public $user;

	/**
	 * @var Email The email getting sent
	 */
	public $email;

	/**
	 * @var array The variables being sent to the email message template
	 */
	public $variables;
}
