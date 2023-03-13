<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mfa\type;

use Craft;
use craft\base\mfa\BaseMfaType;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\web\View;

class EmailCode extends BaseMfaType
{
    /**
     * @inheritdoc
     */
    public static bool $requiresSetup = false;

    /**
     * The key to store the authenticator secret in session, while setting up this method.
     */
    protected const EMAIL_CODE_SESSION_KEY = 'craft.email.code';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Email Code');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Authenticate via single use code sent to your email address.');
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return [
            'verificationCode' => Craft::t('app', 'Verification code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        $user = Craft::$app->getMfa()->getUserForMfa();

        if ($user === null) {
            return '';
        }

        $this->sendOtp($user);

        $data = [
            'user' => $user,
            'fields' => $this->getNamespacedFields(),
            'currentMethod' => self::class,
        ];

        $view = Craft::$app->getView();
        $view->templateMode = View::TEMPLATE_MODE_CP;
        $formHtml = $view->renderTemplate(
            '_components/mfa/emailcode/verification.twig',
            $data
        );

        return parent::getInputHtml($formHtml, $options);
    }

    /**
     * Verify OTP (code) sent via email
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool
    {
        $user = Craft::$app->getMfa()->getUserForMfa();

        if ($user === null) {
            return false;
        }

        $session = Craft::$app->getSession();
        $code = $data['verificationCode'];

        if (strtolower($code) === strtolower($session->get(self::EMAIL_CODE_SESSION_KEY))) {
            $session->remove(self::EMAIL_CODE_SESSION_KEY);

            return true;
        }

        return false;
    }

    // EmailCode-specific methods
    // -------------------------------------------------------------------------

    /**
     * Send OTP code via email
     *
     * @param User $user
     * @return void
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public function sendOtp(User $user): void
    {
        $code = $this->_setOtp();
//        $session = Craft::$app->getSession();

        Craft::$app->getMailer()
            ->composeFromKey('mfa_code_email', ['code' => $code])
            ->setTo($user)
            ->send();
//        $message = Craft::$app->getMailer()
//            ->composeFromKey('mfa_code_email', ['code' => $code])
//            ->setTo($user);

        // todo: make messages show without the reload
//        if ($message->send()) {
//            $session->setNotice(Craft::t('app', 'Verification email sent!'));
//        } else {
//            $session->setError(Craft::t('app', 'Failed to send verification email.'));
//        }
    }

    /**
     * Generate email OPT, store it in session and return the code
     *
     * @return string
     * @throws \craft\errors\MissingComponentException
     */
    private function _setOtp(): string
    {
        $code = StringHelper::toUpperCase(StringHelper::randomString(4) . '-' . StringHelper::randomString(4));
        Craft::$app->getSession()->set(self::EMAIL_CODE_SESSION_KEY, $code);

        return $code;
    }
}
