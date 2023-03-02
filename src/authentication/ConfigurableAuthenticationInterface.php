<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\authentication;

use craft\elements\User;

interface ConfigurableAuthenticationInterface
{
    /**
     * Checks if given MFA method has been fully set up for user to use
     *
     * @param User $user
     * @return bool
     */
    public static function isSetupForUser(User $user): bool;
}
