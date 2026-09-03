<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\LoginUserRetrieving;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;
use Tpetry\QueryExpressions\Function\String\Lower;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\site_url;
use function CraftCms\Cms\template;

readonly class LoginController extends AuthenticationController
{
    public function showLogin(Request $request, GeneralConfig $generalConfig, AuthMethods $authMethods, OAuth $oauth): Response|View|\Inertia\Response
    {
        // see if they're already logged in, unless this is a preview request
        // (see https://github.com/craftcms/cms/discussions/19360)
        if (! $request->isPreview() && ($user = $request->craftUser())) {
            return $this->handleSuccessfulLogin($request, $user);
        }

        // should we be showing the 2FA form?
        if ($request->input('verify')) {
            return redirect()->action([TwoFactorAuthenticationController::class, 'showForm']);
        }

        $oauthLoginButtons = array_map(
            static fn (HtmlString $button): string => (string) $button,
            $oauth->getLoginButtons(),
        );

        return $this->renderViewWithFallback(
            inertiaComponent: 'auth/Login',
            inertiaProps: [
                'username' => $generalConfig->rememberUsernameDuration ? $authMethods->getRememberedUsername() : '',
                'oauthLoginButtons' => $oauthLoginButtons,
                'action' => $request->isCpRequest() ? action([self::class, 'attemptLogin']) : action_url('users/login'),
            ],
            data: [
                'action' => action([self::class, 'attemptLogin']),
                'oauthLoginButtons' => $oauthLoginButtons,
            ],
        );
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

        // if we're showing the modal for session elevation, and we got this far,
        // it means that the time left doesn't exceed minimum requirement,
        // but there might still be some time left;
        // to avoid strange behaviour, clear out whatever's left so that we start with the right amount of time
        // see https://github.com/craftcms/cms/pull/18753
        if ($forElevatedSession) {
            $request->session()->forget('auth.password_confirmed_at');
        }

        // If the current user is being impersonated, get the impersonator instead
        if ($forElevatedSession && ($impersonator = $impersonation->getImpersonator())) {
            $staticEmail = $impersonator->email;
        } else {
            $staticEmail = $request->validate(['email' => ['required']])['email'];
        }

        $html = template('_special/login-modal', [
            'action' => action([LoginController::class, 'attemptLogin']),
            'staticEmail' => $staticEmail,
            'forElevatedSession' => $forElevatedSession,
        ], templateMode: TemplateMode::Cp);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
        ]);
    }

    public function attemptLogin(Request $request, Impersonation $impersonation, LoginRateLimiter $loginRateLimiter): Response
    {
        $request->validate([
            'loginName' => [
                'required',
                'string',
                Rule::when($this->generalConfig->useEmailAsUsername, 'email'),
            ],
            'password' => Password::required(),
            'rememberMe' => ['nullable'],
        ]);

        /**
         * @var EloquentUserProvider $provider
         */
        $provider = auth()->getProvider();

        $user = $this->retrieveLoginUser($request->input('loginName'));

        return new Timebox()->call(function () use ($request, $provider, $user, $impersonation, $loginRateLimiter) {
            if (! $user || $user->getAuthPassword() === null) {
                return $this->handleLoginFailure($request, AuthError::InvalidCredentials);
            }

            if (! $this->auth->authenticate($user, ['password' => $request->input('password')])) {
                return $this->handleLoginFailure($request, $this->auth->authError, $user);
            }

            // Valid credentials
            $loginRateLimiter->clear($request);

            if (config('hashing.rehash_on_login', true) && $user instanceof Model) {
                $provider->rehashPasswordIfRequired($user, ['password' => $request->input('password')]);
            }

            $loginUser = $user;
            $remember = $request->boolean('rememberMe');

            if ($impersonation->isImpersonating()) {
                $loginUser = $request->craftUser() ?? $user;
                $remember = false;
            }

            return $this->finalizeLogin(
                $request,
                $user,
                $remember,
                loginUser: $loginUser,
            );
        }, 30_000);
    }

    private function retrieveLoginUser(string $loginName): ?CraftUser
    {
        event($retrieving = new LoginUserRetrieving($loginName));

        $user = $retrieving->user ?? User::query()
            ->where(function (Builder $query) use ($loginName) {
                $loginName = mb_strtolower($loginName);

                $query->where(new Lower('username'), $loginName)
                    ->orWhere(new Lower('email'), $loginName);
            })
            ->first();

        event($retrieved = new LoginUserRetrieved($loginName, $user));

        return $retrieved->user;
    }

    public function logout(Request $request): Response
    {
        auth()->logout();

        if ($request->wantsJson()) {
            return $this->asSuccess();
        }

        // Redirect to the login page if this is a control panel request
        if ($request->isCpRequest()) {
            return redirect(cp_url(CpAuthPath::Login->value));
        }

        return redirect(site_url($this->generalConfig->getPostLogoutRedirect()));
    }
}
