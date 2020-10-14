<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Authenticate User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AuthenticateUserEvent extends Event
{
    /**
     * @var string The password that was submitted
     */
    public $password;

    /**
     * @var bool Whether authentication should continue. If not, and the user doesn’t authenticate, set [[$authError]] to something,
     * to prevent Craft from considering the user to be authenticated.
     */
    public $performAuthentication = true;

    /**
     * @var string|null The authentication error.
     * @since 3.5.13
     */
    public $authError;
}
