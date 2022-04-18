<?php

declare(strict_types=1);

namespace craft\authentication\base;

use craft\elements\User;

/**
 * UserConfigurableTypeInterface must be implemented by all steps that can be configured by the user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface UserConfigurableTypeInterface
{
    /**
     * Returns `true` if this authentication step requires a setup.
     *
     * @return bool
     */
    public static function getHasUserSetup(): bool;

    /**
     * Returns the HTML form for user setup.
     *
     * @param User $user
     * @return string
     */
    public function getUserSetupFormHtml(User $user): string;
}
