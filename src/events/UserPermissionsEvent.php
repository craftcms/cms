<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * UserPermissionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class UserPermissionsEvent extends Event
{
    /**
     * @var array Permissions array assigned to the user.
     */
    public array $permissions;

    /**
     * @var int ID of the user associated with this event.
     */
    public int $userId;
}
