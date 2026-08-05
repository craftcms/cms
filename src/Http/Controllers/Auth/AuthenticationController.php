<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\InvalidUserToken;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\EmailVerified;
use CraftCms\Cms\User\Events\UserEmailVerifying;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

abstract readonly class AuthenticationController
{
    use RespondsWithFlash;

    public function __construct(
        protected GeneralConfig $generalConfig,
        protected AuthMethods $auth,
    ) {}

    protected function completeLogin(Request $request, CraftUser $user, bool $remember): Response
    {
        auth()->loginUsingId($user->getAuthIdentifier(), $remember);

        return $this->handleSuccessfulLogin($request, $user);
    }

    protected function handleSuccessfulLogin(Request $request, CraftUser $user): Response
    {
        $returnUrl = URL::returnUrl();
        $userElement = $user->asElement();

        if ($request->wantsJson()) {
            return $this->asModelSuccess($userElement, modelName: 'user', data: [
                'returnUrl' => $returnUrl,
            ]);
        }

        return $this->redirectToPostedUrl($userElement, $returnUrl);
    }

    protected function finalizeLogin(
        Request $request,
        CraftUser $user,
        bool $remember,
        bool $skipTwoFactor = false,
        ?CraftUser $loginUser = null,
    ): Response {
        $loginUser ??= $user;

        if (! $skipTwoFactor && ! $this->generalConfig->disable2fa && $this->auth->hasActiveMethod($user)) {
            $this->auth->setUser($user, $remember, $loginUser);

            if (! $request->isCpRequest() && ! $request->wantsJson()) {
                if (! $loginPath = $this->generalConfig->getLoginPath()) {
                    $this->auth->setUser(null);
                    throw new RuntimeException('User requires two-step verification, but the loginPath config setting is disabled.');
                }

                return redirect(\CraftCms\Cms\Support\Url::siteUrl($loginPath, array_filter([
                    'verify' => 1,
                    'returnUrl' => $this->getPostedRedirectUrl($user) ?? URL::returnUrl(),
                ])));
            }

            return redirect()->action([TwoFactorAuthenticationController::class, 'showForm']);
        }

        return $this->completeLogin($request, $loginUser, $remember);
    }

    protected function handleLoginFailure(Request $request, ?AuthError $authError = null, ?CraftUser $user = null): Response
    {
        [$authError, $message] = $this->auth->getLoginFailureInfo($authError, $user);

        event(new Failed(
            guard: Auth::getDefaultDriver(),
            user: $user,
            credentials: $request->only('loginName', 'password'),
        ));

        return $this->asFailure($message, ['errorCode' => $authError?->value]);
    }

    /**
     * @param  array<string, mixed>|null  $inertiaProps
     * @param  array<string, mixed>  $data
     */
    protected function renderViewWithFallback(string $inertiaComponent, ?array $inertiaProps = null, array $data = []): View|InertiaResponse|Response
    {
        $request = request();

        // if this is a front-end request and a template exists for the requested path, render it
        if (! $request->isCpRequest() && view()->exists($request->craftPath())) {
            return view($request->craftPath(), $data);
        }

        TemplateMode::set(TemplateMode::Cp);

        if ($request->isCpRequest()) {
            return Inertia::render($inertiaComponent, $inertiaProps ?? $data);
        }

        // Front-end requests don't run through the `craft.cp` middleware group, so
        // `HandleInertiaRequests` never gets a chance to prep the Inertia root view
        // (headHtml/bodyHtml, shared props, etc). Invoke it manually since we're
        // falling back to an Inertia-rendered response.
        return app(HandleInertiaRequests::class)->handle(
            $request,
            fn (Request $request): Response => Inertia::render($inertiaComponent, $inertiaProps ?? $data)->toResponse($request),
        );
    }

    /** @return Response|array{User, string, string} */
    protected function processTokenRequest(Request $request): Response|array
    {
        $request->validate([
            'uid' => ['required'],
            'code' => ['required'],
        ]);

        /** @var User|null $user */
        $user = User::find()
            ->uid($request->input('uid'))
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        if (! $user) {
            return $this->processInvalidToken($request);
        }

        // If someone is logged in and it’s not this person, log them out
        if ($request->craftUser() && $request->craftUser()->getCraftUserId() !== $user->id) {
            auth()->logout();
        }

        event(new UserEmailVerifying($user));

        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        if (! $broker->tokenExists($user, $request->input('code'))) {
            return $this->processInvalidToken($request, $user);
        }

        event(new EmailVerified($user));

        return [$user, $request->input('uid'), $request->input('code')];
    }

    protected function processInvalidToken(Request $request, ?User $user = null): Response
    {
        event(new InvalidUserToken($user));

        if ($request->wantsJson()) {
            return $this->asFailure('InvalidVerificationCode');
        }

        // If they don't have a verification code at all, and they're already logged-in, just send them to the post-login URL
        if ($user && ! auth()->guest()) {
            return redirect(URL::returnUrl());
        }

        // If the invalidUserTokenPath config setting is set, send them there
        if (! $request->isCpRequest()) {
            $url = Cms::config()->getInvalidUserTokenPath() ?? Cms::config()->getLoginPath();

            return redirect(\CraftCms\Cms\Support\Url::siteUrl($url));
        }

        return redirect(CpAuthPath::Login->value);
    }

    protected function onAfterActivateUser(Request $request, User $user): ?Response
    {
        $this->maybeLoginUserAfterAccountActivation($user);

        if ($request->wantsJson()) {
            return $this->redirectUserToCp($user) ?? $this->redirectUserAfterAccountActivation($user);
        }

        return null;
    }

    protected function maybeLoginUserAfterAccountActivation(User $user): bool
    {
        if (! Cms::config()->autoLoginAfterAccountActivation) {
            return false;
        }

        return (bool) auth()->loginUsingId($user->id);
    }

    protected function redirectUserToCp(User $user): ?Response
    {
        if (! $user->can('accessCp')) {
            return null;
        }

        $postCpLoginRedirect = Cms::config()->getPostCpLoginRedirect();
        $url = \CraftCms\Cms\Support\Url::cpUrl($postCpLoginRedirect);

        return redirect($url);
    }

    protected function redirectUserAfterAccountActivation(User $user): Response
    {
        $activateAccountSuccessPath = Cms::config()->getActivateAccountSuccessPath();
        $url = \CraftCms\Cms\Support\Url::siteUrl($activateAccountSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }

    protected function redirectUserAfterEmailVerification(User $user): Response
    {
        $verifyEmailSuccessPath = Cms::config()->getVerifyEmailSuccessPath();
        $url = \CraftCms\Cms\Support\Url::siteUrl($verifyEmailSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }
}
