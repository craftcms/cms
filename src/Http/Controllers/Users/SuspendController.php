<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Users;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SuspendController
{
    use AuthorizesRequests;
    use RespondsWithFlash;

    public function __construct(
        private Users $users,
    ) {}

    public function suspend(Request $request): Response
    {
        $this->authorize('moderateUsers');

        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        if (! $this->users->canSuspend($request->user(), $user)) {
            return $this->asFailure(t('Couldn’t suspend user.'));
        }

        try {
            $this->users->suspendUser($user);
        } catch (InvalidElementException) {
            return $this->asFailure(t('Couldn’t suspend user.'));
        }

        return $this->asSuccess(t('User suspended.'));
    }

    public function unsuspend(Request $request): Response
    {
        $this->authorize('moderateUsers');

        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        // Even if you have moderateUsers permissions, only and admin should be able to unsuspend another admin.
        if (! $this->users->canSuspend($request->user(), $user)) {
            return $this->asFailure(t('Couldn’t unsuspend user.'));
        }

        try {
            $this->users->unsuspendUser($user);
        } catch (InvalidElementException) {
            return $this->asFailure(t('Couldn’t unsuspend user.'));
        }

        return $this->asSuccess(t('User unsuspended.'));
    }
}
