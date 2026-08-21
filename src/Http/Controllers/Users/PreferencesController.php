<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Http\Requests\UserPreferencesRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserPreferencesViewModel;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PreferencesController
{
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request): CpScreenResponse
    {
        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        $user = $currentUser->asElement();

        return $this->asEditUserScreen($user, EditUserScreens::PREFERENCES)
            ->inertiaPage('users/Preferences', new UserPreferencesViewModel($user));
    }

    public function update(UserPreferencesRequest $request, Users $users): Response
    {
        if (! $user = $request->craftUser()) {
            abort(401);
        }

        $users->saveUserPreferences($user, $request->preferences());

        return $this->asSuccess(t('Preferences saved.'));
    }
}
