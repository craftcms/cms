<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * UserGroupPermissionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class UserGroupPermissionsEvent extends Event
{
    /**
     * @var int ID of the group associated with this event.
     */
    public int $groupId;

    /**
     * @var array Permissions array assigned to the group.
     */
    public array $permissions;
}
