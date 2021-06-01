<?php
declare(strict_types=1);

namespace craft\authentication\base;

use craft\elements\User;

/**
 * Multi-factor authentication step type base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class MfaType extends Type implements MfaTypeInterface
{
    /**
     * @inheritdoc
     */
    public static function hasUserSetup(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getUserSetupFormHtml(User $user): string
    {
        return '';
    }
}
