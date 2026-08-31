<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\SettingPassword;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\User\Validation\UserRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\t;

readonly class SetPasswordController extends AuthenticationController
{
    public function show(Request $request, AuthMethods $auth): Response|View|InertiaResponse
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
        return $this->renderViewWithFallback(
            inertiaComponent: 'auth/SetPassword',
            inertiaProps: [
                'code' => $code,
                'uid' => $uid,
                'newUser' => ! $user->password,
                'action' => $request->isCpRequest() ? action([self::class, 'store']) : action_url('users/set-password'),
                'passwordRules' => Password::defaults()->toPasswordRulesString(),
            ],
            data: [
                'code' => $code,
                'uid' => $uid,
                'newUser' => ! $user->password,
                'passwordRules' => Password::defaults()->toPasswordRulesString(),
            ],
        );
    }

    public function store(Request $request, Users $users, Elements $elements): Response|View|InertiaResponse
    {
        $request->validate([
            'code' => ['required'],
            'uid' => ['required'],
            'newPassword' => ['required', Password::default()],
        ]);

        $user = User::find()
            ->uid($request->input('uid'))
            ->status(null)
            ->addSelect('users.password')
            ->first();

        abort_if(is_null($user), 400, 'Invalid user UUID: '.$request->input('uid'));

        try {
            $status = PasswordFacade::broker()->reset(
                [
                    'token' => $request->input('code'),
                    'email' => $user->email,
                    'password' => $request->input('newPassword'),
                ],
                function (CraftUser $authUser, string $password) use ($elements) {
                    $user = $authUser->asElement();
                    $user->newPassword = $password;
                    $user->ruleset->useScenario(UserRules::SCENARIO_PASSWORD);

                    if (! $elements->saveElement($user)) {
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

            throw ValidationException::withMessages([
                'newPassword' => $user->errors()->get('newPassword') ?: [t('Couldn’t update password.')],
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
