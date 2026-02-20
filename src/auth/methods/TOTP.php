<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\methods;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\helpers\Session as SessionHelper;
use craft\records\Authenticator as AuthenticatorRecord;
use craft\web\assets\totp\TotpAsset;
use craft\web\Session;
use craft\web\View;
use PragmaRX\Google2FA\Exceptions\Google2FAException;
use PragmaRX\Google2FA\Google2FA;
use yii\base\Exception;
use yii\web\ForbiddenHttpException;

/**
 * Time-based one-time password authentication method.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class TOTP extends BaseAuthMethod
{
    /**
     * @var string The session variable name used to store the authenticator
     * secret while setting up this method.
     */
    public string $secretParam;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Authenticator App');
    }

    /**
     * @inheritdoc
     */
    public static function description(): string
    {
        return Craft::t('app', 'Use an authenticator app to verify your identity.');
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->secretParam)) {
            $stateKeyPrefix = md5(sprintf('Craft.%s.%s.%s', Session::class, Craft::$app->id, $this->user->id));
            $this->secretParam = sprintf('%s__secret', $stateKeyPrefix);
        }
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return self::secretFromDb($this->user->id) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getSetupHtml(string $containerId): string
    {
        $secret = $this->secret();
        $totpFormId = sprintf('totp-form-%s', mt_rand());
        $view = Craft::$app->getView();

        $view->registerAssetBundle(TotpAsset::class);
        $view->registerJsWithVars(fn($totpFormId, $containerId) => <<<JS
Craft.createAuthFormHandler(Craft.TotpForm.METHOD, $('#' + $totpFormId), () => {
  Craft.Slideout.instances[$containerId].showSuccess();
  Craft.authMethodSetup.refresh();
});
JS, [
            $view->namespaceInputId($totpFormId),
            $containerId,
        ]);

        return $view->renderTemplate('_components/auth/methods/TOTP/setup.twig', [
            'secret' => rtrim(chunk_split($secret, 4, ' ')),
            'user' => $this->user,
            'qrCode' => $this->generateQrCode($secret),
            'totpFormId' => $totpFormId,
        ], View::TEMPLATE_MODE_CP);
    }

    /**
     * @inheritdoc
     */
    public function getAuthFormHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(TotpAsset::class);
        return $view->renderTemplate('_components/auth/methods/TOTP/form.twig');
    }

    /**
     * @inheritdoc
     */
    public function verify(mixed ...$args): bool
    {
        [$code] = $args;
        if ($code === '') {
            return false;
        }

        $storedSecret = self::secretFromDb($this->user->id);
        $secret = $storedSecret ?? Craft::$app->getSession()->get($this->secretParam);

        if (!$secret) {
            return false;
        }

        $google2fa = new Google2FA();
        try {
            $lastUsedTimestamp = $this->lastUsedTimestamp($this->user->id);
            $verified = $google2fa->verifyKeyNewer($secret, $code, $lastUsedTimestamp);
        } catch (Google2FAException) {
            return false;
        }

        if (!$verified) {
            return false;
        }

        if (!$storedSecret) {
            $this->storeSecret($this->user->id, $secret);
            Craft::$app->getSession()->remove($this->secretParam);
        } else {
            $this->storeLastUsedTimestamp($this->user->id, $verified === true ? $google2fa->getTimestamp() : $verified);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function remove(): void
    {
        AuthenticatorRecord::deleteAll([
            'userId' => $this->user->id,
        ]);
    }

    /**
     * Returns User's 2FA secret from the database
     * or generates a new one.
     *
     * @return string
     */
    private function secret(): string
    {
        $google2fa = new Google2FA();
        $secret = self::secretFromDb($this->user->id);

        if (empty($secret)) {
            try {
                $secret = $google2fa->generateSecretKey(32);
                SessionHelper::set($this->secretParam, $secret);
            } catch (\Exception $e) {
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        return $secret;
    }

    /**
     * Returns user's 2fa secret from the database.
     *
     * @param int $userId
     * @return string|null
     */
    private static function secretFromDb(int $userId): ?string
    {
        $record = AuthenticatorRecord::find()
            ->select(['auth2faSecret'])
            ->where(['userId' => $userId])
            ->one();

        return $record ? $record['auth2faSecret'] : null;
    }

    /**
     * Stores user's 2fa secret in the database.
     *
     * @param int $userId
     * @param string $secret
     * @return void
     * @throws ForbiddenHttpException
     */
    private function storeSecret(int $userId, string $secret): void
    {
        // Make sure they have an elevated session first
        if (!Craft::$app->getUser()->getHasElevatedSession()) {
            throw new ForbiddenHttpException(Craft::t('app', 'This action may only be performed with an elevated session.'));
        }

        /** @var AuthenticatorRecord|null $record */
        $record = AuthenticatorRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            $record = new AuthenticatorRecord();
            $record->userId = $userId;
        }

        $record->auth2faSecret = $secret;
        // whenever we store the secret, we should ensure the oldTimestamp is accurate too
        $record->oldTimestamp = (new Google2FA())->getTimestamp();
        $record->save();
    }

    /**
     * Returns the totp's old timestamp.
     *
     * @param int $userId
     * @return int|null
     */
    private function lastUsedTimestamp(int $userId): ?int
    {
        $record = AuthenticatorRecord::find()
            ->select(['oldTimestamp'])
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            return null;
        }

        // old timestamp is the current Unix Timestamp divided by the $keyRegeneration period
        // so we store it as int and don't mess with it
        return $record['oldTimestamp'];
    }

    /**
     * Saves totp's old timestamp.
     *
     * @param int $userId
     * @param int $timestamp
     * @return void
     */
    private function storeLastUsedTimestamp(int $userId, int $timestamp): void
    {
        /** @var AuthenticatorRecord|null $record */
        $record = AuthenticatorRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$record) {
            // you shouldn't be able to get here without having a record, so let's throw an exception
            throw new Exception('Couldn\'t find authenticator record.');
        }

        $record->oldTimestamp = $timestamp;
        $record->save();
    }

    /**
     * Generates and returns a QR code based on given 2fa secret.
     *
     * @param string $secret
     * @return string
     */
    private function generateQrCode(string $secret): string
    {
        $qrCodeUrl = (new Google2FA())->getQRCodeUrl(
            Craft::$app->getSystemName(),
            $this->user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(150, 0),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($qrCodeUrl);
    }
}
