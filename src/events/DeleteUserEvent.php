<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\User;

/**
 * Delete user event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteUserEvent extends UserEvent
{
    // Properties
    // =========================================================================

    /**
     * @var User The user model that the deleted user's content is getting transfered to.
     */
    public $transferContentTo;
}
