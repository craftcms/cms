<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\elements\User;
use craft\mail\Message;
use yii\base\Event;

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
     * @var string|array|User|User[]|null The user(s) the email was being sent to.
     */
    public $user;

    /**
     * @var Message|null The email message.
     */
    public $email;

    /**
     * @var array|null The status the elements are getting set to.
     */
    public $variables;

    /**
     * @var string|null The error message.
     */
    public $error;
}
