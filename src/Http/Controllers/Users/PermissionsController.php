<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\elements\User as UserElement;
use craft\errors\InvalidElementException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Events\AssigningGroupsAndPermissions;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class PermissionsController
{
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request, ?User $user = null): CpScreenResponse
    {
        $user = $this->editedUser($user?->id);

        $response = $this->asEditUserScreen($user, self::SCREEN_PERMISSIONS);
        $response->action('users/save-permissions');
        $response->contentTemplate('users/_permissions', [
            'user' => $user,
            'currentGroupIds' => Arr::pluck($user->getGroups(), 'id'),
        ]);

        if (! $user->getIsCredentialed() && $user->username && $request->user()->can('moderateUsers')) {
            $response->additionalButtonsHtml(
                Html::button(t('Save and send activation email'), [
                    'class' => ['btn', 'secondary', 'formsubmit'],
                    'data' => [
                        'param' => 'sendActivationEmail',
                        'value' => '1',
                    ],
                ])
            );
        }

        return $response;
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'userId' => ['required', 'integer', Rule::exists(Table::USERS, 'id')],
            'admin' => ['nullable', 'boolean'],
            'sendActivationMail' => ['nullable', 'boolean'],
        ]);

        $currentUser = $request->user();
        $user = $this->editedUser($request->integer('userId'));

        // Is their admin status changing?
        if ($currentUser->admin) {
            $adminParam = $request->boolean('admin', $user->admin);

            if ($adminParam !== $user->admin) {
                if ($adminParam) {
                    $this->requireElevatedSession();
                }

                $user->admin = $adminParam;
                Craft::$app->getElements()->saveElement($user, false);
            }
        }

        if (Edition::get()->value >= Edition::Pro->value) {
            if (Event::hasListeners(AssigningGroupsAndPermissions::class)) {
                Event::dispatch(new AssigningGroupsAndPermissions($user));
            }

            // Assign user groups and permissions if the current user is allowed to do that
            $this->saveUserGroups($request, $user, $currentUser);
            $this->saveUserPermissions($request, $user, $currentUser);

            if (Event::hasListeners(GroupsAndPermissionsAssigned::class)) {
                Event::dispatch(new GroupsAndPermissionsAssigned($user));
            }
        }

        if (
            ! $user->getIsCredentialed() &&
            $currentUser->can('administrateUsers') &&
            $request->boolean('sendActivationEmail')
        ) {
            try {
                if (! Craft::$app->getUsers()->sendActivationEmail($user)) {
                    Flash::fail(t('Couldn’t send activation email. Check your email settings.'));
                }
            } catch (InvalidElementException $e) {
                Flash::fail(t('Couldn’t send the activation email: {error}', [
                    'error' => $e->getMessage(),
                ]));
            }
        }

        return $this->asSuccess(t('Permissions saved.'));
    }

    private function saveUserGroups(Request $request, UserElement $user, UserElement $currentUser): void
    {
        $groupIds = $request->input('groups');

        if ($groupIds === null) {
            return;
        }

        if ($groupIds === '') {
            $groupIds = [];
        }

        $allGroups = UserGroups::getAllGroups()->keyBy('id');

        // See if there are any new groups in here
        $oldGroupIds = Arr::pluck($user->getGroups(), 'id');
        $hasNewGroups = false;
        $newGroups = [];

        foreach ($groupIds as $groupId) {
            $group = $newGroups[] = $allGroups[$groupId];

            if (! in_array($groupId, $oldGroupIds, false)) {
                $hasNewGroups = true;

                // Make sure the current user is in the group or has permission to assign it
                abort_if(
                    ! $currentUser->can("assignUserGroup:$group->uid"),
                    403,
                    "Your account doesn't have permission to assign user group “{$group->name}” to a user.",
                );
            }
        }

        if ($hasNewGroups) {
            $this->requireElevatedSession();
        }

        Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);

        $user->setGroups($newGroups);
    }

    private function saveUserPermissions(Request $request, UserElement $user, UserElement $currentUser): void
    {
        if (! $currentUser->can('assignUserPermissions')) {
            return;
        }

        // Save any user permissions
        if ($user->admin) {
            $permissions = [];
        } else {
            $permissions = $request->input('permissions');

            if ($permissions === null) {
                return;
            }

            // it will be an empty string if no permissions were assigned during user saving.
            if ($permissions === '') {
                $permissions = [];
            }
        }

        // See if there are any new permissions in here
        $hasNewPermissions = false;

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                $hasNewPermissions = true;

                // Make sure the current user even has permission to grant it
                abort_if(
                    ! $currentUser->can($permission),
                    403,
                    "Your account doesn't have permission to assign the $permission permission to a user.",
                );
            }
        }

        if ($hasNewPermissions) {
            $this->requireElevatedSession();
        }

        UserPermissions::saveUserPermissions($user->id, $permissions);
    }
}
