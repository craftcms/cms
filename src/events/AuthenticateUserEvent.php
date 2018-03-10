<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Authenticate User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AuthenticateUserEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The password that was submitted
     */
    public $password;

    /**
     * @var bool Whether authentication should continue
     */
    public $performAuthentication = true;
}
