<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\View;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Timebox;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;

final readonly class LoginController extends AuthenticationController
{
    public function showLogin(Request $request)
    {
        // see if they're already logged in
        if ($user = $request->user()) {
            return $this->handleSuccessfulLogin($request, $user);
        }

        // should we be showing the 2FA form?
        if ($request->input('verify')) {
            return redirect()->action([TwoFactorAuthenticationController::class, 'showForm']);
        }

        // TODO: _rerouteWithFallbackTemplate??
        return view('craftcms::login');
    }

    public function showLoginModal(Request $request, Impersonation $impersonation): JsonResponse
    {
        $forElevatedSession = $request->boolean('forElevatedSession');

        // If the current user is being impersonated, get the impersonator instead
        if ($forElevatedSession && ($impersonator = $impersonation->getImpersonator())) {
            $staticEmail = $impersonator->email;
        } else {
            $staticEmail = $request->validate(['email' => ['required']])['email'];
        }

        $view = Craft::$app->getView();
        $html = $view->renderTemplate('_special/login-modal.twig', [
            'staticEmail' => $staticEmail,
            'forElevatedSession' => $forElevatedSession,
        ], View::TEMPLATE_MODE_CP);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    public function attemptLogin(Request $request, Impersonation $impersonation): Response
    {
        $request->validate([
            'loginName' => ['required', 'string'],
            'password' => ['required', 'string'],
            'rememberMe' => ['nullable'],
        ]);

        /** @var \CraftCms\Cms\Auth\UserProvider $provider */
        $provider = Auth::guard('craft')->getProvider();
        $user = $provider->retrieveByCredentials($request->only('loginName', 'password'));

        return new Timebox()->call(function () use ($request, $provider, $user, $impersonation) {
            if (! $user || $user->password === null) {
                return $this->handleLoginFailure($request, User::AUTH_INVALID_CREDENTIALS);
            }

            if (! $provider->validateCredentials($user, ['password' => $request->input('password')])) {
                return $this->handleLoginFailure($request, $user->authError, $user);
            }

            // Valid credentials
            if (config('hashing.rehash_on_login', true)) {
                $provider->rehashPasswordIfRequired($user, ['password' => $request->input('password')]);
            }

            $authService = Craft::$app->getAuth();
            if (! $this->generalConfig->disable2fa && $authService->hasActiveMethod($user)) {
                $request->session()->put('user.id', $user->id);

                if (! $request->isCpRequest() && ! $request->wantsJson()) {
                    $loginPath = $this->generalConfig->getLoginPath();

                    if (! $loginPath) {
                        $request->session()->forget('user.id');
                        throw new RuntimeException('User requires two-step verification, but the loginPath config setting is disabled.');
                    }

                    return redirect(UrlHelper::siteUrl($loginPath, array_filter([
                        'verify' => 1,
                        'returnUrl' => $this->getPostedRedirectUrl($user),
                    ])));
                }

                return redirect()->action([TwoFactorAuthenticationController::class, 'showForm']);
            }

            // if we're impersonating, pass the user we're impersonating to the complete method
            if ($impersonation->isImpersonating()) {
                $user = Auth::user() ?? $user;
            }

            return $this->completeLogin($request, $user, $request->boolean('rememberMe'));
        }, 30_000);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('craft')->logout();

        if ($request->wantsJson()) {
            return $this->asSuccess();
        }

        // Redirect to the login page if this is a control panel request
        if ($request->isCpRequest()) {
            return redirect(cp_url(CpAuthPath::Login->value));
        }

        return $this->asSuccess(
            redirect: $this->generalConfig->getPostLogoutRedirect(),
        );
    }
}
