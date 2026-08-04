<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserProfileViewModel;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class UsersController
{
    use AuthorizesRequests;
    use EditUserTrait;
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function create(Request $request, Drafts $drafts): Response
    {
        $user = new User;

        $this->authorize('save', $user);

        $user->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        if (! $drafts->saveElementAsDraft($user, $request->craftUser()?->getCraftUserId(), markAsSaved: false)) {
            return $this->asModelFailure($user, mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => User::lowerDisplayName(),
            ])), 'user');
        }

        $editUrl = $user->getCpEditUrl();

        if (! $request->wantsJson()) {
            return redirect(Url::urlWithParams($editUrl, ['fresh' => 1]));
        }

        return $this->asModelSuccess($user, t('{type} created.', [
            'type' => User::displayName(),
        ]), 'user', array_filter([
            'cpEditUrl' => $request->isCpRequest() ? $editUrl : null,
        ]));
    }

    public function edit(?int $userId = null): Response|CpScreenResponse
    {
        $user = $this->editedUser($userId);

        if ($user->getIsCurrent()) {
            return $this->asEditUserScreen($user, self::SCREEN_PROFILE)
                ->redirectUrl('myaccount')
                ->inertiaPage('users/Profile', new UserProfileViewModel($user));
        }

        /**
         * Let the elements/edit action do most of the work
         */
        $response = app(EditElementController::class)->setElement($user)();

        if (! $response instanceof CpScreenResponse) {
            return $response;
        }

        return $this->asEditUserScreen($user, self::SCREEN_PROFILE, $response)
            ->when(
                $user->getIsUnpublishedDraft() && $this->showPermissionsScreen(),
                function (CpScreenResponse $response) use ($user) {
                    $response
                        ->submitButtonLabel(t('Create and set permissions'))
                        ->redirectUrl($this->editUserScreenUrl($user, self::SCREEN_PERMISSIONS));
                },
            );
    }
}
