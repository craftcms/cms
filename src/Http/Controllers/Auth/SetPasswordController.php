<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\SettingPassword;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class SetPasswordController extends AuthenticationController
{
    public function show(Request $request, Auth $auth): Response|View
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

        $auth->setRememberedUsername($user);

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

        try {
            $status = PasswordFacade::broker('craft')->reset(
                [
                    'token' => $request->input('code'),
                    'loginName' => $user->email,
                    'password' => $request->input('newPassword'),
                ],
                function (User $user, string $password) {
                    $user->newPassword = $password;
                    $user->setScenario(User::SCENARIO_PASSWORD);

                    if (! Craft::$app->getElements()->saveElement($user)) {
                        throw new RuntimeException('Couldn’t update password.');
                    }
                }
            );
        } catch (RuntimeException) {
            $status = 'password.save_failed';
        }

        event($event = new SettingPassword($user, $request->input('code'), $request->input('newPassword'), $status));

        $status = $event->status;

        if ($status === 'password.save_failed') {
            if ($request->wantsJson()) {
                return $this->asFailure(
                    t('Couldn’t update password.'),
                    $user->errors()->get('newPassword'),
                );
            }

            return $this->renderViewWithFallback('set-password', [
                'errors' => $user->errors()->get('newPassword'),
                'code' => $request->input('code'),
                'id' => $request->input('id'),
                'newUser' => ! $user->password,
            ]);
        }

        if ($status !== PasswordFacade::PASSWORD_RESET) {
            return $this->processInvalidToken($request, $user);
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
            $url = Url::cpUrl(CpAuthPath::Login->value);
        } else {
            // Send them to the 'setPasswordSuccessPath' by default
            $setPasswordSuccessPath = Cms::config()->getSetPasswordSuccessPath();
            $url = Url::siteUrl($setPasswordSuccessPath);
        }

        return $this->redirectToPostedUrl($user, $url);
    }
}
