<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authenticators;

use craft\base\Component;
use craft\elements\User;

/**
 * Result returned from an authenticate call.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class AuthenticationResult extends Component
{
    /**
     * User associated with the authentication attempt.
     *
     * @var User|null
     */
    public ?User $user = null;

    /**
     * Add an authentication error to the component.
     *
     * @param string|null $authError Error key.
     * @return void
     */
    public function addAuthError(?string $authError = null): void
    {
        $this->addError('authError', $authError);
    }

    /**
     * Return the first auth error
     *
     * @return string|null
     */
    public function getAuthError(): ?string
    {
        return $this->getFirstError('authError');
    }

}