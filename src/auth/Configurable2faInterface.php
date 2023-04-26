<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\elements\User;

interface Configurable2faInterface
{
    /**
     * Checks if given 2FA method has been fully set up for user to use
     *
     * @param User $user
     * @return bool
     */
    public function isSetupForUser(User $user): bool;

    /**
     * Get html for 2FA setup form
     *
     * @param string $html
     * @param bool $withInto
     * @param User|null $user
     * @return string
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string;

    /**
     * Remove all setup user has for this 2FA option
     *
     * @return bool
     */
    public function removeSetup(): bool;
}
