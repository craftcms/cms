<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\User;
use yii\base\Event;

/**
 * LoginUserNotFoundEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Bert Oost <hello@bertoost.com>
 * @since 4.1.4.1
 */
class LoginUserNotFoundEvent extends Event
{
    /**
     * @var string The loginName which is tried to login
     */
    public string $loginName;

    /**
     * @var User|null The user that the login was attempted for, handled by the event
     */
    public ?User $user = null;
}
