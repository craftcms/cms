<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class ActivateController
{
    use AuthorizesRequests;
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function __construct(
        private Users $users,
    ) {}

    public function activate(Request $request): Response
    {
        $this->authorize('administrateUsers');

        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $userVariable = $request->getSigned('userVariable') ?? 'user';
        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        try {
            $this->users->activateUser($user);
        } catch (InvalidElementException $e) {
            return $this->asModelFailure(
                $user,
                t('There was a problem activating the user: {error}', [
                    'error' => $e->getMessage(),
                ]),
                $userVariable,
            );
        }

        return $this->asModelSuccess(
            $user,
            t('Successfully activated the user.'),
            $userVariable,
        );
    }

    public function deactivate(Request $request): Response
    {
        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        if ($user->id !== $request->user()->id) {
            $this->authorize('administrateUsers');

            // Even if you have administrateUsers permissions, only and admin should be able to deactivate another admin.
            abort_if($user->admin && ! $request->user()->isAdmin(), 403, 'User is not authorized to perform this action.');
        }

        // Deactivate the user
        try {
            $this->users->deactivateUser($user);

            return $this->asSuccess(t('Successfully deactivated the user.'));
        } catch (InvalidElementException) {
            return $this->asFailure(t('There was a problem deactivating the user.'));
        }
    }

    public function sendActivationEmail(Request $request): Response
    {
        $validated = $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = User::find()
            ->id($validated['userId'])
            ->status(null)
            ->addSelect('users.password')
            ->first();

        abort_if(is_null($user), 400, 'User not found');

        abort_if(
            ! in_array($user->getStatus(), [User::STATUS_PENDING, User::STATUS_INACTIVE], true),
            400,
            'Activation emails can only be sent to inactive or pending users',
        );

        if (! $user->pending) {
            $this->requirePermission('moderateUsers');
        }

        $userVariable = $request->getSigned('userVariable') ?? 'user';

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            return $this->asModelFailure(
                $user,
                t('Couldn’t send the activation email: {error}', [
                    'error' => $e->getMessage(),
                ]),
                $userVariable,
            );
        }

        return $this->asSuccess(t('Activation email sent.'));
    }
}
