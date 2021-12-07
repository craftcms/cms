<?php
declare(strict_types=1);

namespace craft\authentication\type;

use Craft;
use craft\authentication\base\Type;
use craft\elements\User;
use craft\helpers\User as UserHelper;
use craft\models\authentication\State;

/**
 * This step type authenticates a known user by password.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $inputFieldHtml
 */
class Password extends Type
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate using a password.');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['password'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): State
    {
        if (!$user) {
            return $this->state;
        }

        // If we don't have a password hash, try fetching the identity.
        if (empty($user->password)) {
            $user = User::findIdentity($user->id) ?? $user;
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$user->id || !$user->authenticate($credentials['password'])) {
            return $this->failToAuthenticate($user);
        }

        return $this->completeStep($user);
    }

    /**
     * Set authentication failure message on the state and return it.
     *
     * @param User $user The User model
     * @return State
     */
    protected function failToAuthenticate(?User $user): State
    {
        Craft::$app->getSession()->setError(UserHelper::getLoginFailureMessage(User::AUTH_INVALID_CREDENTIALS, $user));
        return $this->state;
    }

    /**
     * @inheritdoc
     */
    public function getInputFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/Password/input');
    }
}
