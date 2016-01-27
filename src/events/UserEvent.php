<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;

/**
 * User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var User The user model associated with the event.
     */
    public $user;
}
