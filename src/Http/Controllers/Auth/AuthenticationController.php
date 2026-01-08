<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\helpers\User as UserHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
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
}
