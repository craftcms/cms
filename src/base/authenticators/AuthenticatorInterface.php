<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authenticators;

use yii\web\Response;

/**
 * AuthenticatorInterface defines the common interface to be implemented by authenticator classes.
 *
 * An abstract implementation is provided by [[BaseAuthenticator]]
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 * @mixin BaseAuthenticator
 */
interface AuthenticatorInterface
{
    /**
     * Main authentication method. Handles all the business logic for
     * authentication of a user.
     *
     * @return AuthenticationResult
     */
    public function authenticate(): AuthenticationResult;

    /**
     * Get the HTML string that should be rendered on the login page
     * for this authenticator.
     *
     * @return string|null
     */
    public function getLoginHtml(): ?string;

    /**
     * @return Response|null
     */
    public function handleAuthenticationRequest(): ?Response;
}