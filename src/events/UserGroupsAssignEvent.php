<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * User Groups assign event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroupsAssignEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The user ID associated with this event
     */
    public $userId;

    /**
     * @var int[] The user group IDs being assigned to the user
     */
    public $groupIds;
}
