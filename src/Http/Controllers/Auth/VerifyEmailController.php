<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class VerifyEmailController extends AuthenticationController
{
    public function show(Request $request): Response|View
    {
        if (! is_array($info = $this->processTokenRequest($request))) {
            return $info;
        }

        /** @var User $user */
        /** @var string $uid */
        /** @var string $code */
        [$user, $uid, $code] = $info;

        app(Auth::class)->setRememberedUsername($user);

        // Send them to the set verify-email template
        return $this->renderViewWithFallback('verify-email', [
            'id' => $uid,
            'code' => $code,
        ]);
    }

    public function store(Request $request, Users $users): Response|View
    {
        $request->validate([
            'code' => ['required'],
            'uid' => ['required'],
        ]);

        $user = $users->getUserByUid($request->input('uid'));

        abort_if(! $user, 400, 'Invalid user UUID: '.$request->input('uid'));

        /** @var PasswordBroker $broker */
        $broker = Password::broker('craft');
        if (! $broker->tokenExists($user, $request->input('code'))) {
            return $this->processInvalidToken($request, $user);
        }

        $pending = $user->pending;

        // Do they have an unverified email?
        if ($user->unverifiedEmail) {
            try {
                $users->verifyEmailForUser($user);
            } catch (InvalidElementException) {
                return view('_special/emailtaken', [
                    'email' => $user->unverifiedEmail,
                ]);
            }
        } elseif ($pending) {
            // No unverified email so just get on with activating their account
            $users->activateUser($user);
        }

        if ($request->user()) {
            Flash::success(t('Email verified'));
        }

        // Were they just activated?
        if ($pending && ($response = $this->onAfterActivateUser($request, $user)) !== null) {
            return $response;
        }

        return $this->redirectUserToCp($user) ?? $this->redirectUserAfterEmailVerification($user);
    }
}
