<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Methods;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Craft;
use craft\web\assets\totp\TotpAsset;
use craft\web\View;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\Cms;
use Illuminate\Session\SessionManager;
use PragmaRX\Google2FA\Exceptions\Google2FAException;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

use function CraftCms\Cms\t;

/*
 * Time-based one-time password authentication method.
 */
final class TOTP extends BaseAuthMethod
{
    use ConfirmsPasswords;

    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return t('Authenticator App');
    }

    /**
     * {@inheritdoc}
     */
    public static function description(): string
    {
        return t('Use an authenticator app to verify your identity.');
    }

    public function __construct(
        private readonly SessionManager $session,
        /**
         * @var string The session variable name used to store the authenticator
         *             secret while setting up this method.
         */
        public ?string $secretParam = null,
    ) {}

    public function getSecretParam(): string
    {
        if (isset($this->secretParam)) {
            return $this->secretParam;
        }

        $stateKeyPrefix = md5(sprintf('Craft.%s.%s', config('app.name'), $this->user->id));

        $this->secretParam = sprintf('%s__secret', $stateKeyPrefix);

        return $this->secretParam;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return self::secretFromDb($this->user->id) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetupHtml(string $containerId): string
    {
        $secret = $this->secret();
        $totpFormId = sprintf('totp-form-%s', mt_rand());
        $view = Craft::$app->getView();

        $view->registerAssetBundle(TotpAsset::class);
        $view->registerJsWithVars(fn ($totpFormId, $containerId) => <<<JS
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
     * {@inheritdoc}
     */
    public function getAuthFormHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(TotpAsset::class);

        return $view->renderTemplate('_components/auth/methods/TOTP/form.twig');
    }

    /**
     * {@inheritdoc}
     */
    public function verify(mixed ...$args): bool
    {
        [$code] = $args;

        if ($code === '') {
            return false;
        }

        $storedSecret = self::secretFromDb($this->user->id);
        $secret = $storedSecret ?? $this->session->get($this->getSecretParam());

        if (! $secret) {
            return false;
        }

        $google2fa = new Google2FA;

        try {
            $lastUsedTimestamp = $this->lastUsedTimestamp($this->user->id);
            $verified = $google2fa->verifyKeyNewer($secret, $code, $lastUsedTimestamp);
        } catch (Google2FAException) {
            return false;
        }

        if (! $verified) {
            return false;
        }

        if (! $storedSecret) {
            $this->storeSecret($this->user->id, $secret);
            $this->session->forget($this->getSecretParam());
        } else {
            $this->storeLastUsedTimestamp($this->user->id, $verified === true ? $google2fa->getTimestamp() : $verified);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(): void
    {
        Authenticator::where('userId', $this->user->id)->delete();
    }

    /**
     * Returns User's 2FA secret from the database
     * or generates a new one.
     */
    private function secret(): string
    {
        $google2fa = new Google2FA;
        $secret = self::secretFromDb($this->user->id);

        if (empty($secret)) {
            try {
                $secret = $google2fa->generateSecretKey(32);
                $this->session->put($this->getSecretParam(), $secret);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $secret;
    }

    /**
     * Returns user's 2fa secret from the database.
     */
    private static function secretFromDb(int $userId): ?string
    {
        return Authenticator::query()
            ->select('auth2faSecret')
            ->where('userId', $userId)
            ->value('auth2faSecret');
    }

    /**
     * Stores user's 2fa secret in the database.
     */
    private function storeSecret(int $userId, string $secret): void
    {
        // Make sure they have an elevated session first
        $this->requireConfirmedPassword();

        $model = Authenticator::firstOrNew(['userId' => $userId]);

        // whenever we store the secret, we should ensure the oldTimestamp is accurate too
        $model->oldTimestamp = new Google2FA()->getTimestamp();
        $model->auth2faSecret = $secret;
        $model->save();
    }

    private function lastUsedTimestamp(int $userId): ?int
    {
        // old timestamp is the current Unix Timestamp divided by the $keyRegeneration period
        // so we store it as int and don't mess with it
        return Authenticator::query()
            ->select('oldTimestamp')
            ->where('userId', $userId)
            ->value('oldTimestamp');
    }

    private function storeLastUsedTimestamp(int $userId, int $timestamp): void
    {
        Authenticator::query()
            ->where('userId', $userId)
            // you shouldn't be able to get here without having a record, so let's throw an exception
            ->firstOrFail()
            ->update([
                'oldTimestamp' => $timestamp,
            ]);
    }

    /**
     * Generates and returns a QR code based on given 2fa secret.
     */
    private function generateQrCode(string $secret): string
    {
        $qrCodeUrl = new Google2FA()->getQRCodeUrl(
            Cms::systemName(),
            $this->user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(150, 0),
            new SvgImageBackEnd
        );

        return new Writer($renderer)->writeString($qrCodeUrl);
    }
}
