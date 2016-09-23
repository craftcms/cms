<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * User Groups assign event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserGroupsAssignEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var integer The user ID associated with this event
     */
    public $userId;

    /**
     * @var integer[] The user group IDs being assigned to the user
     */
    public $groupIds;
}
