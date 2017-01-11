<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\elements\User;
use yii\base\Event;

/**
 * User token event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserTokenEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var User|null The user model associated with the event.
     */
    public $user;
}
