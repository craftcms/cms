<?php
declare(strict_types=1);

namespace craft\authentication\type\mfa;

use Craft;
use craft\authentication\base\MfaType;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Message;
use craft\models\authentication\State;

/**
 * This step type sends a single-use-password to the user's email and requires the password to be entered for the step to be completed.
 * This step type requires a user to be identified by a previous step.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $inputFieldHtml
 */
class EmailCode extends MfaType
{
    protected const CODE_KEY = 'craft.authentication.data.emailCode';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticate with email');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Receive an email with the verification code');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return ['verification-code'];
    }

    /**
     * @inheritdoc
     */
    public function prepareForAuthentication(User $user = null): void
    {
        // TODO ensure a user is identified beforehand, likely in a parent class.
        $code = StringHelper::randomString(4).'-'.StringHelper::randomString(4).'-'.StringHelper::randomString(4);
        $session = Craft::$app->getSession();
        $session->set(static::CODE_KEY, $code);

        /** @var Message $message */
        $message = Craft::$app->getMailer()
            ->compose()
            ->setSubject('Hello')
            ->setTextBody('Here is a code: ' . $code)
            ->setTo($user);

        if ($message->send()) {
            $session->setNotice(Craft::t('app', 'Verification email sent!'));
        } else {
            $session->setError(Craft::t('app', 'Failed to send verification email.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): State
    {
        if (is_null($user) || empty($credentials['verification-code'])) {
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

    /**
     * @inheritdoc
     */
    public function getInputFieldHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/authenticationsteps/EmailCode/input');
    }
}
