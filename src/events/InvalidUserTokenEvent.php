<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\User;
use yii\base\Event;

/**
 * InvalidUserTokenEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.5
 */
class InvalidUserTokenEvent extends Event
{
    /**
     * @var User|null The user account the request is associated with, if a valid user UID was passed.
     */
    public ?User $user = null;
}
