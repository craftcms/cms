<?php
declare(strict_types=1);

namespace craft\authentication\type;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\helpers\User as UserHelper;
use craft\models\AuthenticationState;

class Credentials extends Step
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('app', 'Credentials');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('app', 'Authenticate using username or email, and password.');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['loginName', 'password'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        $potentialUser = Craft::$app->getUsers()->getUserByUsernameOrEmail($credentials['loginName']);

        if (!$potentialUser || $potentialUser->password === null) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
            return $this->failToAuthenticate();
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$potentialUser->authenticate($credentials['password'])) {
            return $this->failToAuthenticate();
        }

        return $this->completeStep($potentialUser);
    }

    /**
     * Set authentication failure message on the state and return it.
     *
     * @return AuthenticationState
     */
    protected function failToAuthenticate(): AuthenticationState
    {
        Craft::$app->getSession()->setError(UserHelper::getLoginFailureMessage(User::AUTH_INVALID_CREDENTIALS));
        return $this->state;
    }

    public function getFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/Credentials/input');
    }
}
