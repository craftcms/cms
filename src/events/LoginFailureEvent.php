<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;

/**
 * LoginFailureEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LoginFailureEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The authentication error key, if the reason is known.
     * @see User
     */
    public $authError;

    /**
     * @var User|null The user that the login was attempted for, if there was a username/email match
     */
    public $user;
}
