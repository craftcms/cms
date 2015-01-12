<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

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
	 * @var \craft\app\models\User The user receiving the email
	 */
	public $user;

	/**
	 * @var \craft\app\models\Email The email getting sent
	 */
	public $email;

	/**
	 * @var array The variables being sent to the email message template
	 */
	public $variables;
}
