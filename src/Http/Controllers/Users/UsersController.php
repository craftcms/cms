<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\ViewModels\UserEditViewModel;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
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

    /**
     * The account's Profile screen — the user's field layout, rendered through
     * the shared element editor. Both "My Account" and another user's account
     * land here; the account navigation beside it comes from the view model.
     */
    public function edit(ElementRequest $request, ?int $userId = null): Response|InertiaResponse
    {
        $user = $this->editedUser($userId);

        // A plugin can take screens off the list, so this one isn't a given
        // even though nothing in core removes it.
        abort_unless(
            isset(app(EditUserScreens::class)->screens($user)[EditUserScreens::PROFILE]),
            403,
            'User not authorized to perform this action.',
        );

        return Inertia::render('users/Edit', new UserEditViewModel(
            user: $user,
            request: $request,
            canSave: $request->craftUser()->can('save', $user),
        ));
    }
}
