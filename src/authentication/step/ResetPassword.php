<?php
declare(strict_types=1);

namespace craft\authentication\step;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\models\AuthenticationState;

class ResetPassword extends Step
{
    /**
     * @inheritdoc
     */
    public function getFields(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        Craft::$app->getSession()->setNotice(Craft::t('app', 'Password reset email sent.'));

        if ($user) {
            Craft::$app->getUsers()->sendPasswordResetEmail($user);
        }

        return $this->completeStep($user);
    }

    /**
     * @inheritdoc
     */
    public function getFieldHtml(): string
    {
        return '';
    }
}
