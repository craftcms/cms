<?php
declare(strict_types=1);

namespace craft\authentication\step\recovery;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\models\AuthenticationState;

class EmailResetPasswordUrl extends Step
{
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
