<?php

declare(strict_types=1);

namespace craft\authentication\base;

use craft\elements\User;

/**
 * MfaTypeInterface must be implemented by all steps that serve as a multi-factor authentication step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface MfaTypeInterface
{
    /**
     * Returns `true` if the MFA type is available for a given user.
     */
    public static function isAvailableForUser(User $user): bool;
}
