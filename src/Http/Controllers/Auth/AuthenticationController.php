<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\InvalidUserToken;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\EmailVerified;
use CraftCms\Cms\User\Events\VerifyingEmail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

abstract readonly class AuthenticationController
{
    use RespondsWithFlash;

    public function __construct(
        protected GeneralConfig $generalConfig,
    ) {}

    protected function completeLogin(Request $request, User $user, bool $remember): Response
    {
        Auth::guard('craft')->login($user, $remember);

        return $this->handleSuccessfulLogin($request, $user);
    }

    protected function handleSuccessfulLogin(Request $request, User $user): Response
    {
        $returnUrl = URL::returnUrl();

        if ($request->wantsJson()) {
            return $this->asModelSuccess($user, modelName: 'user', data: [
                'returnUrl' => $returnUrl,
            ]);
        }

        return $this->redirectToPostedUrl($user, $returnUrl);
    }

    protected function handleLoginFailure(Request $request, ?string $authError = null, ?User $user = null): Response
    {
        [$authError, $message] = UserHelper::getLoginFailureInfo($authError, $user);

        Event::dispatch(new Failed(
            guard: 'craft',
            user: $user,
            credentials: $request->only('loginName', 'password'),
        ));

        return $this->asFailure($message, ['errorCode' => $authError]);
    }

    protected function renderViewWithFallback(string $cpTemplate, array $data = []): View
    {
        if (view()->exists(request()->path())) {
            return view(request()->path(), $data);
        }

        Craft::$app->getView()->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);

        return view(Str::start($cpTemplate, 'craftcms::'), $data);
    }

    protected function processTokenRequest(Request $request): Response|array
    {
        $request->validate([
            'id' => ['required'],
            'code' => ['required'],
        ]);

        /** @var User|null $user */
        $user = User::find()
            ->uid($request->input('id'))
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        if (! $user) {
            return $this->processInvalidToken($request);
        }

        // If someone is logged in and it’s not this person, log them out
        if ($request->user() && $request->user()->id !== $user->id) {
            Auth::logout();
        }

        if (Event::hasListeners(VerifyingEmail::class)) {
            Event::dispatch(new VerifyingEmail($user));
        }

        if (! Users::isVerificationCodeValidForUser($user, $request->input('code'))) {
            return $this->processInvalidToken($request, $user);
        }

        if (Event::hasListeners(EmailVerified::class)) {
            Event::dispatch(new EmailVerified($user));
        }

        return [$user, $request->input('id'), $request->input('code')];
    }

    protected function processInvalidToken(Request $request, ?User $user = null): Response
    {
        Event::dispatch(new InvalidUserToken($user));

        if ($request->wantsJson()) {
            return $this->asFailure('InvalidVerificationCode');
        }

        // If they don't have a verification code at all, and they're already logged-in, just send them to the post-login URL
        if ($user && ! $user->verificationCode && ! Auth::guest()) {
            return redirect(URL::returnUrl());
        }

        // If the invalidUserTokenPath config setting is set, send them there
        if (! $request->isCpRequest()) {
            $url = Cms::config()->getInvalidUserTokenPath() ?? Cms::config()->getLoginPath();

            return redirect(UrlHelper::siteUrl($url));
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

        Auth::login($user);

        return true;
    }

    protected function redirectUserToCp(User $user): ?Response
    {
        if (! $user->can('accessCp')) {
            return null;
        }

        $postCpLoginRedirect = Cms::config()->getPostCpLoginRedirect();
        $url = UrlHelper::cpUrl($postCpLoginRedirect);

        return redirect($url);
    }

    protected function redirectUserAfterAccountActivation(User $user): Response
    {
        $activateAccountSuccessPath = Cms::config()->getActivateAccountSuccessPath();
        $url = UrlHelper::siteUrl($activateAccountSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }

    protected function redirectUserAfterEmailVerification(User $user): Response
    {
        $verifyEmailSuccessPath = Cms::config()->getVerifyEmailSuccessPath();
        $url = UrlHelper::siteUrl($verifyEmailSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }
}
