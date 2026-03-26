<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class EnableController
{
    use RespondsWithFlash;

    public function __construct(
        private Users $users,
    ) {}

    public function __invoke(Request $request): Response
    {
        $request->validate([
            'userId' => ['required', 'int'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        $elementsService = Craft::$app->getElements();

        abort_if(! $elementsService->canSave($user), 403, 'User is not authorized to perform this action.');

        $user->enabled = true;
        $user->enabledForSite = true;
        $user->archived = false;

        if (! $elementsService->saveElement($user, false)) {
            return $this->asFailure(mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ])));
        }

        return $this->asSuccess(t('{type} saved.', [
            'type' => User::displayName(),
        ]));
    }
}
