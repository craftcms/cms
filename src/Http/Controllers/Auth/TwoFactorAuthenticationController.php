<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\auth\methods\RecoveryCodes;
use craft\auth\methods\TOTP;
use craft\helpers\UrlHelper;
use craft\web\View;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class TwoFactorAuthenticationController
{
    use RespondsWithFlash;

    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function showForm(Request $request, Impersonation $impersonation): Response|string
    {
        $userSession = Craft::$app->getUser();

        $user = $impersonation->getImpersonator()
            ?? User::find()->id($request->session()->get('user.id'))->first();

        if (! $user) {
            if ($request->isSiteRequest()) {
                if (! $loginPath = $this->generalConfig->getLoginPath()) {
                    throw new RuntimeException('The loginPath config setting is disabled.');
                }

                return redirect($loginPath);
            }

            return redirect(CpAuthPath::Login);
        }

        $activeMethods = Craft::$app->getAuth()->getActiveMethods($user);
        $methodClass = $request->input('method');

        if ($methodClass) {
            /** @var AuthMethodInterface|null $method */
            $method = Arr::first(
                $activeMethods,
                fn (AuthMethodInterface $method) => $method::class === $methodClass,
            );

            abort_if(! $method, 400, 'Invalid method class: '.$methodClass);

            $activeMethods = array_values(array_filter($activeMethods, fn ($m) => $m !== $method));
        } else {
            abort_if(empty($activeMethods), 400, 'User has no active two-step verification methods.');

            $method = array_shift($activeMethods);
        }

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = $method->getAuthFormHtml();
        } finally {
            $view->setTemplateMode($templateMode);
        }

        $returnUrl = $request->input('returnUrl');
        if (! $returnUrl) {
            if ($request->isCpRequest()) {
                // explicitly set the default return URL here, since checkPermission('accessCp') will be false
                $defaultReturnUrl = UrlHelper::cpUrl($this->generalConfig->getPostCpLoginRedirect());
            } else {
                $defaultReturnUrl = UrlHelper::siteUrl($this->generalConfig->getPostLoginRedirect());
            }

            $returnUrl = $userSession->getReturnUrl($defaultReturnUrl);
        }

        $authFormData = [
            'authMethod' => $method::class,
            'otherMethods' => array_map(fn (AuthMethodInterface $method) => [
                'name' => $method::displayName(),
                'class' => $method::class,
            ], $activeMethods),
            'authForm' => $html,
            'returnUrl' => $returnUrl,
        ];

        if ($request->wantsJson()) {
            return new JsonResponse([
                ...$authFormData,
                'headHtml' => $view->getHeadHtml(),
                'bodyHtml' => $view->getBodyHtml(),
            ]);
        }

        return $view->renderTemplate('login.twig', compact('authFormData'), View::TEMPLATE_MODE_CP);
    }

    public function verify(Request $request): Response
    {
        $code = $request->input('code');

        $authService = Craft::$app->getAuth();

        if (! $authService->verify(TOTP::class, $code)) {
            return $this->asFailure($authService->getAuthErrorMessage());
        }

        return $this->asSuccess(t('Verification successful.'));
    }

    public function verifyRecoveryCode(Request $request): Response
    {
        $code = $request->input('code');

        $authService = Craft::$app->getAuth();

        if (! $authService->verify(RecoveryCodes::class, $code)) {
            return $this->asFailure($authService->getAuthErrorMessage(t('Invalid recovery code.')));
        }

        return $this->asSuccess(t('Verification successful.'));
    }
}
