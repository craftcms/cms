<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\authenticators;

use Craft;
use craft\base\authenticators\AuthenticationResult;
use craft\base\authenticators\AuthenticatorInterface;
use craft\base\authenticators\BaseAuthenticator;
use craft\elements\User;

class LoginFormAuthenticator extends BaseAuthenticator implements AuthenticatorInterface
{
    public ?string $handle = 'loginForm';

    public function authenticate(?User $user = null): AuthenticationResult
    {
        $password = \Craft::$app->getRequest()->getRequiredBodyParam('password');

        $response = new AuthenticationResult();

        if (!$user || $user->password === null) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
            $response->addAuthError(User::AUTH_INVALID_CREDENTIALS);
            return $response;
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$user->authenticate($password)) {
            $response->addAuthError($user->authError);
            return $response;
        }

        return $response;
    }

    public static function getLoginHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticators/loginForm.twig');
    }
}