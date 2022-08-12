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
 * FindLoginUserEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Bert Oost <hello@bertoost.com>
 * @since 4.2.0
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
