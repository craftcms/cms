<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\User\Elements\User;

/**
 * InvalidUserTokenEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.5
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Events\InvalidUserToken} instead.
 */
class InvalidUserTokenEvent extends Event
{
    /**
     * @var User|null The user account the request is associated with, if a valid user UID was passed.
     */
    public ?User $user = null;
}
