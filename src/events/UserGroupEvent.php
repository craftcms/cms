<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\UserGroup;
use yii\base\Event;

/**
 * UserGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserGroupEvent extends Event
{
    /**
     * @var UserGroup The user group associated with this event
     */
    public UserGroup $userGroup;

    /**
     * @var bool Whether the user group is brand new
     */
    public bool $isNew = false;
}
