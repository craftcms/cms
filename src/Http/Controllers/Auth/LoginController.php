<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\UserProvider;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Timebox;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\template;

final readonly class LoginController extends AuthenticationController
{
    public function showLogin(Request $request): Response|View
    {
        // see if they're already logged in
        if ($user = $request->user()) {
            return $this->handleSuccessfulLogin($request, $user);
        }

        // should we be showing the 2FA form?
        if ($request->input('verify')) {
            return redirect()->action([TwoFactorAuthenticationController::class, 'showForm']);
        }

        return $this->renderViewWithFallback('login');
    }

    /**
     * Redirects the user to the default post-login URL.
     */
    public function redirect(): Response
    {
        return redirect(URL::defaultReturnUrl());
    }

    public function showLoginModal(Request $request, Impersonation $impersonation, HtmlStack $HtmlStack): JsonResponse
    {
        $forElevatedSession = $request->boolean('forElevatedSession');

        // If the current user is being impersonated, get the impersonator instead
        if ($forElevatedSession && ($impersonator = $impersonation->getImpersonator())) {
            $staticEmail = $impersonator->email;
        } else {
            $staticEmail = $request->validate(['email' => ['required']])['email'];
        }

        $html = template('_special/login-modal', [
            'staticEmail' => $staticEmail,
            'forElevatedSession' => $forElevatedSession,
        ], templateMode: TemplateMode::Cp);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
        ]);
    }

    public function attemptLogin(Request $request, Auth $auth, Impersonation $impersonation): Response
    {
        $request->validate([
            'loginName' => ['required', 'string'],
            'password' => ['required', 'string'],
            'rememberMe' => ['nullable'],
        ]);

        /**
         * @var UserProvider $provider
         *
         * @phpstan-ignore method.notFound
         */
        $provider = auth('craft')->getProvider();

        $user = $provider->retrieveByCredentials($request->only('loginName', 'password'));

        return new Timebox()->call(function () use ($request, $provider, $user, $impersonation) {
            if (! $user || $user->password === null) {
                return $this->handleLoginFailure($request, AuthError::InvalidCredentials);
            }

            if (! $provider->validateCredentials($user, ['password' => $request->input('password')])) {
                return $this->handleLoginFailure($request, $provider->getAuthError(), $user);
            }

            // Valid credentials
            if (config('hashing.rehash_on_login', true)) {
                $provider->rehashPasswordIfRequired($user, ['password' => $request->input('password')]);
            }

            // if we're impersonating, pass the user we're impersonating to the complete method
            if ($impersonation->isImpersonating()) {
                $user = $request->user() ?? $user;
            }

            return $this->finalizeLogin($request, $user, $request->boolean('rememberMe'));
        }, 30_000);
    }

    public function logout(Request $request): Response
    {
        auth('craft')->logout();

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
