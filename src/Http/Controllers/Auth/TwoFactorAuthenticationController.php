<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class TwoFactorAuthenticationController
{
    use ConfirmsPasswords;
    use RespondsWithFlash;

    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function showForm(Request $request, AuthMethods $auth, Impersonation $impersonation, HtmlStack $HtmlStack): RedirectResponse|\Inertia\Response
    {
        $user = $impersonation->getImpersonator()
            ?? User::find()->id($request->session()->get('user.id'))->first();

        $pendingAt = $request->session()->get('user.pending_2fa_at');
        $elapsedTime = now()->timestamp - $pendingAt;
        $challengeExpired = ! $pendingAt || $elapsedTime > 300;
        if ($challengeExpired) {
            $request->session()->forget(['user.id', 'user.pending_2fa_at']);
        }

        if (! $user || $challengeExpired) {
            if ($request->isSiteRequest()) {
                if (! $loginPath = $this->generalConfig->getLoginPath()) {
                    throw new RuntimeException('The loginPath config setting is disabled.');
                }

                return redirect($loginPath);
            }

            return redirect(cp_url(CpAuthPath::Login->value));
        }

        $activeMethods = $auth->getActiveMethods($user);
        $methodHandle = $request->input('method');

        if ($methodHandle) {
            /** @var AuthMethodInterface|null $method */
            $method = $activeMethods->first(
                fn (AuthMethodInterface $method) => $method::handle() === $methodHandle,
            );

            abort_if(! $method, 400, 'Invalid method handle: '.$methodHandle);
        } else {
            abort_if($activeMethods->isEmpty(), 400, 'User has no active two-step verification methods.');

            $method = $activeMethods->first();
        }

        $activeMethods = $activeMethods->filter(fn ($m) => $m !== $method)->values();

        $returnUrl = $request->input('returnUrl');
        if (! $returnUrl) {
            if ($request->isCpRequest()) {
                // explicitly set the default return URL here, since checkPermission('accessCp') will be false
                $defaultReturnUrl = \CraftCms\Cms\Support\Url::cpUrl($this->generalConfig->getPostCpLoginRedirect());
            } else {
                $defaultReturnUrl = \CraftCms\Cms\Support\Url::siteUrl($this->generalConfig->getPostLoginRedirect());
            }

            $returnUrl = URL::returnUrl($defaultReturnUrl);
        }

        $html = TemplateMode::with(
            TemplateMode::Cp,
            fn () => $method->getAuthFormHtml([
                'returnUrl' => Crypt::encrypt($returnUrl),
            ]),
        );

        $authFormData = [
            'authMethod' => $method::class,
            'otherMethods' => $activeMethods->map(fn (AuthMethodInterface $method) => [
                'name' => $method::displayName(),
                'handle' => $method::handle(),
            ])->all(),
            'authForm' => $html,
            'returnUrl' => $returnUrl,
        ];

        if ($request->wantsJson()) {
            return new JsonResponse([
                ...$authFormData,
                'headHtml' => $HtmlStack->headHtml(),
                'bodyHtml' => $HtmlStack->bodyHtml(),
            ]);
        }

        return Inertia::render('Auth/Challenge', compact('authFormData'));
        // return template('login', compact('authFormData'), templateMode: TemplateMode::Cp);
    }

    public function verify(Request $request, AuthMethods $auth): Response
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $currentUser = $request->user();

        if (! $auth->verifyMethod(TOTP::class, $request->input('code'))) {
            return $this->asFailure($auth->getAuthMethodErrorMessage());
        }

        if ($currentUser) {
            $this->confirmPassword();

            return $this->asJsonSuccess(data: [
                'elevatedSessionExpiresAt' => $this->elevatedSessionExpiresAt(),
            ]);
        }

        if ($request->wantsJson()) {
            return $this->asJsonSuccess(data: [
                'returnUrl' => $this->getPostedRedirectUrl() ?? URL::returnUrl(),
            ]);
        }

        return $this->asSuccess(t('Verification successful.'));
    }

    public function verifyRecoveryCode(Request $request, AuthMethods $auth): Response
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $currentUser = $request->user();

        if (! $auth->verifyMethod(RecoveryCodes::class, $request->input('code'))) {
            return $this->asFailure($auth->getAuthMethodErrorMessage(t('Invalid recovery code.')));
        }

        if ($currentUser) {
            $this->confirmPassword();

            return $this->asJsonSuccess(data: [
                'elevatedSessionExpiresAt' => $this->elevatedSessionExpiresAt(),
            ]);
        }

        if ($request->wantsJson()) {
            return $this->asJsonSuccess(data: [
                'returnUrl' => $this->getPostedRedirectUrl() ?? URL::returnUrl(),
            ]);
        }

        return $this->asSuccess(t('Verification successful.'));
    }
}
