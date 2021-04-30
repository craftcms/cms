<?php
declare(strict_types=1);

namespace craft\authentication\type;

use Craft;
use craft\authentication\base\Type;
use craft\elements\User;
use craft\models\AuthenticationState;

/**
 * This step type authenticates a user by an email address.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Email extends Type
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('app', 'Authenticate with email');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['email'];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        $email = $credentials['email'];
        $potentialUser = User::find()->email($email)->one();

        if (!$potentialUser) {
            if (Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
                return $this->completeStep(new User);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Invalid username or email.'));

            return $this->state;
        }

        return $this->completeStep($potentialUser);
    }

    /**
     * @inheritdoc
     */
    public function getFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/Email/input');
    }
}
