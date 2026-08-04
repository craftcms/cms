<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\UserGroup;

/**
 * Define user groups event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.4
 */
class DefineUserGroupsEvent extends UserEvent
{
    /**
     * @var UserGroup[] The user groups to assign to the user
     */
    public array $userGroups;
}
