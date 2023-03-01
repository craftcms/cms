<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authentication;

use craft\elements\User;

interface BaseAuthenticationInterface
{
    /**
     * Name of the MFA authentication method
     *
     * @return string
     */
    public static function displayName(): string;

    /**
     * Description of the MFA authentication method
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Get html for MFA verification form
     *
     * @return string
     */
    public function getFormHtml(User $user): string;

    /**
     * Returns the array of field names used in the MFA verification form
     *
     * @return array|null
     */
    public function getFields(): ?array;

    /**
     * Verify provided MFA code
     *
     * @param User $user
     * @param string $verificationCode
     * @return bool
     */
    public function verify(User $user, string $verificationCode): bool;
}
