<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\models\UserGroup;

/**
 * UserGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserGroupEvent extends \yii\base\Event
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
