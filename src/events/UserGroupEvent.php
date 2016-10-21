<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\UserGroup;

/**
 * UserGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var UserGroup The user group associated with this event
     */
    public $userGroup;

    /**
     * @var boolean Whether the user group is brand new
     */
    public $isNew = false;
}
