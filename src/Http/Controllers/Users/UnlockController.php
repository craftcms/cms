<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Users;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

final readonly class UnlockController
{
    use AuthorizesRequests;
    use RespondsWithFlash;

    public function __invoke(Request $request, Users $users, Impersonation $impersonation)
    {
        $this->authorize('moderateUsers');

        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = $users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        if ($user->admin) {
            abort_if(! $request->user()->isAdmin(), 403, 'Only admins can unlock other admins.');
            abort_if($user->id === $impersonation->getImpersonatorId(), 403, 'You can’t unlock yourself via impersonation.');
        }

        $users->unlockUser($user);

        return $this->asSuccess(t('User unlocked.'));
    }
}
