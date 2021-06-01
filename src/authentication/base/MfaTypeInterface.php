<?php
declare(strict_types=1);

namespace craft\authentication\base;

use craft\elements\User;

/**
 * Multi-factor authentication type interface. This interface must be implemented by all steps that serve as a multi-factor authentication step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface MfaTypeInterface
{
    /**
     * Returns `true` if this authentication step requires a setup.
     *
     * @return bool
     */
    public static function hasUserSetup(): bool;

    /**
     * Returns the HTML form for user setup.
     *
     * @param User $user
     * @return string
     */
    public function getUserSetupFormHtml(User $user): string;
}
