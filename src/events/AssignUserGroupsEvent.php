<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Assign User Groups event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssignUserGroupsEvent extends Event
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
