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
 * @since 3.0.0
 * @deprecated in 4.5.4. [[DefineUserGroupsEvent]] should be used instead.
 */
class UserAssignGroupEvent extends DefineUserGroupsEvent
{
}
