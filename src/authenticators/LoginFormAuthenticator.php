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
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\FindLoginUserEvent;

/**
 * Default form authenticator.
 */
class LoginFormAuthenticator extends BaseAuthenticator implements AuthenticatorInterface
{
    /**
     * @inheritdoc
     */
    public ?string $handle = 'loginForm';

    public function authenticate(?User $user = null): AuthenticationResult
    {
        $password = Craft::$app->getRequest()->getRequiredBodyParam('password');
        $loginName = Craft::$app->getRequest()->getRequiredBodyParam('loginName');
        $rememberMe = (bool)Craft::$app->getRequest()->getBodyParam('rememberMe');

        $result = new AuthenticationResult();
        $result->user = $this->_findUserByLoginName($loginName);

        if (!$result->user || $result->user->password === null) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
            $result->addAuthError(User::AUTH_INVALID_CREDENTIALS);
            return $result;
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$result->user->authenticate($password)) {
            $result->addAuthError($result->user->authError);
            return $result;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        // Try logging them in
        $userSession = Craft::$app->getUser();
        if (!$userSession->login($result->user, $duration)) {
            // Unknown error
            $result->addAuthError();
            return $result;
        }

        return $result;
    }

    public function getLoginHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticators/loginForm.twig');
    }

    private function _findUserByLoginName($loginName): ?User
    {
        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
        ]);
        $this->trigger(UsersController::EVENT_BEFORE_FIND_LOGIN_USER, $event);

        $user = $event->user ?? Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
            'user' => $user,
        ]);

        $this->trigger(UsersController::EVENT_AFTER_FIND_LOGIN_USER, $event);
        return $event->user;
    }
}