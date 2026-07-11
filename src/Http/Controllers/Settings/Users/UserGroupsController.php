<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use CraftCms\Cms\User\UserGroups;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

class UserGroupsController extends BaseUserSettingsController
{
    use ConfirmsPasswords;
    use EnforcesPermissions;
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly UserGroups $userGroups,
    ) {
        $this->readOnly = ! $this->generalConfig->allowAdminChanges;
    }

    public function index()
    {
        if (Edition::get() === Edition::Team) {
            return redirect()->action([self::class, 'edit'], $this->userGroups->getTeamGroup()->id);
        }

        return Inertia::render('settings/users/groups/Index', [
            'crumbs' => $this->crumbs(t('User Groups')),
            'title' => t('User Settings'),
            'subnav' => $this->subnav(),
            'groups' => $this->userGroups->getAllGroups(),
        ]);
    }

    public function create(UserPermissions $userPermissions): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('Create a new user group'))
            ->crumbs($this->crumbs(t('User Groups')))
            ->redirectUrl('settings/users')
            ->inertiaPage('settings/users/groups/Edit', [
                'group' => new UserGroup,
                'brandNew' => true,
                'permissions' => $userPermissions->getAllPermissions(),
            ]);
    }

    public function edit(UserGroupModel $userGroup, UserPermissions $userPermissions): CpScreenResponse|View
    {
        if (Edition::get() === Edition::Team) {
            $group = $this->userGroups->getTeamGroup();

            return view('settings/users/groups/_team', [
                'group' => $group,
                'readOnly' => $this->readOnly,
            ]);
        }

        $group = $this->userGroups->getGroupById($userGroup->id);

        return new CpScreenResponse()
            ->editUrl($group->getCpEditUrl())
            ->title(trim($group->name) ?: t('Edit User Group'))
            ->crumbs(array_merge($this->crumbs(t('User Groups'), cp_url('settings/users')), [
                ['label' => $group->name],
            ]))
            ->redirectUrl('settings/users')
            ->inertiaPage('settings/users/groups/Edit', [
                'group' => [
                    'id' => $group->id,
                    ...$group->getConfig(true),
                ],
                'brandNew' => false,
                'permissions' => $userPermissions->getAllPermissions(),
            ])
            ->prepareScreen(function (CpScreenResponse $response, string $containerId) {
                HtmlStack::jsWithVars(
                    fn ($containerId) => <<<JS
                        new Craft.ElevatedSessionForm('#' + $containerId, [
                            '.user-permissions input[type="checkbox"]:not(:checked)'
                        ]);
                    JS,
                    [$containerId],
                );
            })
            ->when($this->readOnly, function (CpScreenResponse $response) {
                $response->noticeHtml(app(ContentHtml::class)->readOnlyNoticeHtml());
            });
    }

    public function store(Request $request, UserPermissions $userPermissions): Response
    {
        $userGroupData = new UserGroup;
        $userGroupData->id = $request->integer('id', $request->input('groupId'));
        $userGroupData->name = $request->input('name');
        $userGroupData->handle = $request->input('handle');
        $userGroupData->description = $request->input('description');
        $userGroupData->uid = $request->input('uid');

        $userGroupData->validate(throw: true);

        if (Edition::get() === Edition::Team) {
            $group = $this->userGroups->getTeamGroup();
            $userGroupData->name = $group->name;
            $userGroupData->handle = $group->handle;
            $userGroupData->description = $group->description;
        } elseif ($userGroupData->id) {
            $group = $this->userGroups->getGroupById($userGroupData->id);
        } else {
            $group = $userGroupData;
        }

        $group->name = $userGroupData->name;
        $group->handle = $userGroupData->handle;
        $group->description = $userGroupData->description;

        $isNewGroup = ! $group->id;

        if (! $this->userGroups->saveGroup($group)) {
            throw ValidationException::withMessages($group->errors()->getMessages());
        }

        $permissions = $request->array('permissions');

        if (! $isNewGroup) {
            foreach ($permissions as $permission) {
                if ($group->can($permission)) {
                    continue;
                }

                // Yep. This will require an elevated session
                $this->requireConfirmedPassword();
                break;
            }
        }

        if (Edition::get() === Edition::Team) {
            $permissions[] = 'accessCp';
        } elseif ($isNewGroup) {
            // assignNewUserGroup => assignUserGroup:<uid>
            $assignNewGroupKey = array_search('assignNewUserGroup', $permissions);
            if ($assignNewGroupKey !== false) {
                $permissions[$assignNewGroupKey] = "assignUserGroup:$group->uid";
            }
        }

        $userPermissions->saveGroupPermissions($group->id, $permissions);

        $message = Edition::get() === Edition::Team
            ? t('Permissions saved.')
            : t('Group saved.');

        return $this->asSuccess($message);
    }

    public function destroy(Request $request, int $groupId): Response
    {
        $this->userGroups->deleteGroupById($groupId);

        return $this->asSuccess(t('Group deleted.'), redirect: route('craft.cp.settings.users.index'));
    }
}
