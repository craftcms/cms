<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class TwoFactorAuthenticationController
{
    use RespondsWithFlash;

    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function showForm(Request $request, AuthMethods $auth, Impersonation $impersonation, HtmlStack $HtmlStack): Response|string
    {
        $user = $impersonation->getImpersonator()
            ?? User::find()->id($request->session()->get('user.id'))->first();

        if (! $user) {
            if ($request->isSiteRequest()) {
                if (! $loginPath = $this->generalConfig->getLoginPath()) {
                    throw new RuntimeException('The loginPath config setting is disabled.');
                }

                return redirect($loginPath);
            }

            return redirect(CpAuthPath::Login->value);
        }

        $activeMethods = $auth->getActiveMethods($user);
        $methodHandle = $request->input('method');

        if ($methodHandle) {
            /** @var AuthMethodInterface|null $method */
            $method = $activeMethods->first(
                fn (AuthMethodInterface $method) => $method::handle() === $methodHandle,
            );

            abort_if(! $method, 400, 'Invalid method handle: '.$methodHandle);

            $activeMethods = $activeMethods->filter(fn ($m) => $m !== $method)->values();
        } else {
            abort_if($activeMethods->isEmpty(), 400, 'User has no active two-step verification methods.');

            $method = $activeMethods->first();
        }

        $returnUrl = $request->input('returnUrl');

        if ($returnUrl) {
            $returnUrl = preg_replace('/[\t\r\n]/', '', trim((string) $returnUrl));
            if (Str::startsWith(Str::lower($returnUrl), 'javascript:')) {
                $returnUrl = null;
            }
        }

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
            fn () => $method->getAuthFormHtml($returnUrl),
        );

        $authFormData = [
            'authMethod' => $method::class,
            'otherMethods' => $activeMethods
                ->filter(fn (AuthMethodInterface $authMethod) => $authMethod::handle() !== $method::handle())
                ->map(fn (AuthMethodInterface $method) => [
                    'name' => $method::displayName(),
                    'handle' => $method::handle(),
                    'url' => action([TwoFactorAuthenticationController::class, 'showForm'], ['method' => $method::handle()]),
                ])->values(),
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

        return template('login', compact('authFormData'), templateMode: TemplateMode::Cp);
    }

    public function verify(Request $request, AuthMethods $auth): Response
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $request->input('code');

        if (! $auth->verifyMethod(TOTP::class, $code)) {
            return $this->asFailure($auth->getAuthMethodErrorMessage());
        }

        return $this->asSuccess(t('Verification successful.'));
    }

    public function verifyRecoveryCode(Request $request, AuthMethods $auth): Response
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $request->input('code');

        if (! $auth->verifyMethod(RecoveryCodes::class, $code)) {
            return $this->asFailure($auth->getAuthMethodErrorMessage(t('Invalid recovery code.')));
        }

        return $this->asSuccess(t('Verification successful.'));
    }
}
