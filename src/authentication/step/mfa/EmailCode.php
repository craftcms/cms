<?php
declare(strict_types=1);

namespace craft\authentication\step\mfa;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\models\AuthenticationState;

class EmailCode extends Step
{
    protected const CODE_KEY = 'craft.authentication.data.emailCode';

    /**
     * @inheritdoc
     */
    public function getFields(): array
    {
        return ['verification-code'];
    }

    /**
     * @inheritdoc
     */
    public function prepareForAuthentication(User $user = null): void
    {
        $code = StringHelper::randomString(4).'-'.StringHelper::randomString(4).'-'.StringHelper::randomString(4);
        $session = Craft::$app->getSession();
        $session->set(static::CODE_KEY, $code);

        $message = Craft::$app->getMailer()
            ->compose()
            ->setSubject('Hello')
            ->setTextBody('Here is a code: ' . $code)
            ->setTo($user);

        if ($message->send()) {
            $session->setNotice(Craft::t('app', 'Verification email sent!' . $code));
        } else {
            $session->setError(Craft::t('app', 'Failed to send verification email.'));
        }
    }


    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        if (is_null($user)) {
            return $this->state;
        }

        $code = $credentials['verification-code'];
        $session = Craft::$app->getSession();

        if (empty($code) || $code !== $session->get(static::CODE_KEY)) {
            $session->setError(Craft::t('app', 'The verification code is incorrect.'));
            return $this->state;
        }
        $session->remove(static::CODE_KEY);

        return $this->completeStep($user);
    }

    public function getFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/EmailCode/input');
    }
}
