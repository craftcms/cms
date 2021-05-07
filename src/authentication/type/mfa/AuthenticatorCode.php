<?php
declare(strict_types=1);

namespace craft\authentication\type\mfa;

use Craft;
use craft\authentication\base\Type;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Message;
use craft\models\AuthenticationState;

/**
 * This step type requires the user to enter TOTP
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticatorCode extends Type
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate with Google Authenticator');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Enter an authentication code provided by Google Authenticator.');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['authenticator-code'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        if (is_null($user) || empty($credentials['authenticator-code'])) {
            return $this->state;
        }

        $code = $credentials['authenticator-code'];
        $session = Craft::$app->getSession();

        if (empty($code) || $code !== '123-456') {
            $session->setError(Craft::t('app', 'The verification code is incorrect.'));
            return $this->state;
        }

        return $this->completeStep($user);
    }

    public function getFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/AuthenticatorCode/input');
    }
}
