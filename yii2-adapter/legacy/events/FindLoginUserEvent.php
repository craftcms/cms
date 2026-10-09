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
 * FindLoginUserEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Bert Oost <hello@bertoost.com>
 * @since 4.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Events\LoginUserRetrieving} or {@see \CraftCms\Cms\Auth\Events\LoginUserRetrieved} instead.
 */
class FindLoginUserEvent extends Event
{
    /**
     * @var string The provided email or username
     */
    public string $loginName;

    /**
     * @var User|null The resolved user, if any.
     *
     * If this is set by an event handler, that will be the user that is attempted to be signed in.
     */
    public ?User $user = null;
}
