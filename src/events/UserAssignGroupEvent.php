<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\User;

/**
 * User assign group event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserAssignGroupEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var User|null The user model associated with this event
     */
    public $user;
}
