<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;
use craft\app\mail\Message;

/**
 * Send Email Error event raised when there was a problem sending an email.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MailFailureEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|array|User|User[] The user(s) the email was being sent to.
     */
    public $user;

    /**
     * @var Message The email message.
     */
    public $email;

    /**
     * @var array The status the elements are getting set to.
     */
    public $variables;

    /**
     * @var string The error message.
     */
    public $error;
}
