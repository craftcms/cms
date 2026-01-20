<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SetPasswordController extends AuthenticationController
{
    public function show(Request $request): Response|View
    {
        if (! is_array($info = $this->processTokenRequest($request))) {
            return $info;
        }

        /**
         * @var User $user
         * @var string $uid
         * @var string $code
         */
        [$user, $uid, $code] = $info;

        app(Auth::class)->setRememberedUsername($user);

        // Send them to the set password template.
        return $this->renderViewWithFallback('set-password', [
            'code' => $code,
            'id' => $uid,
            'newUser' => ! $user->password,
        ]);
    }

    public function store(Request $request, Users $users): Response|View
    {
        $request->validate([
            'code' => ['required'],
            'id' => ['required'],
            'newPassword' => ['required', Password::default()],
        ]);

        $user = User::find()
            ->uid($request->input('id'))
            ->status(null)
            ->addSelect('users.password')
            ->first();

        abort_if(is_null($user), 400, 'Invalid user UUID: '.$request->input('id'));

        if (! $users->isVerificationCodeValidForUser($user, $request->input('code'))) {
            return $this->processInvalidToken($request, $user);
        }

        $user->newPassword = $request->input('newPassword');
        $user->setScenario(User::SCENARIO_PASSWORD);

        if (! Craft::$app->getElements()->saveElement($user)) {
            if ($request->wantsJson()) {
                return $this->asFailure(
                    t('Couldn’t update password.'),
                    $user->getErrors('newPassword'),
                );
            }

            return $this->renderViewWithFallback('set-password', [
                'errors' => $user->getErrors('newPassword'),
                'code' => $request->input('code'),
                'id' => $request->input('id'),
                'newUser' => ! $user->password,
            ]);
        }

        // If they're pending, try to activate them, and maybe treat this as an activation request
        if ($user->getStatus() === User::STATUS_PENDING) {
            try {
                $users->activateUser($user);
                if ($response = $this->onAfterActivateUser($request, $user)) {
                    return $response;
                }
            } catch (InvalidElementException) {
                // NBD
            }
        }

        if ($request->wantsJson()) {
            return $this->asSuccess(data: [
                'status' => $user->getStatus(),
            ]);
        }

        if ($request->isCpRequest()) {
            // Send them to the control panel login page by default
            $url = UrlHelper::cpUrl(CpAuthPath::Login->value);
        } else {
            // Send them to the 'setPasswordSuccessPath' by default
            $setPasswordSuccessPath = Cms::config()->getSetPasswordSuccessPath();
            $url = UrlHelper::siteUrl($setPasswordSuccessPath);
        }

        return $this->redirectToPostedUrl($user, $url);
    }
}
