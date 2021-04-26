<?php
declare(strict_types=1);

namespace craft\authentication\step;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\models\AuthenticationState;

class Email extends Step
{
    /**
     * @inheritdoc
     */
    public function getFields(): array
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
                // Fake it
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
