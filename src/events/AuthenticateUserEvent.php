<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Authenticate User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AuthenticateUserEvent extends Event
{
    /**
     * @var string|null The password that was submitted, or null if a passkey is being used.
     */
    public ?string $password = null;

    /**
     * @var bool Whether authentication should continue. If not, and the user doesn’t authenticate, set `$event->sender->authError` to something,
     * to prevent Craft from considering the user to be authenticated.
     */
    public bool $performAuthentication = true;
}
