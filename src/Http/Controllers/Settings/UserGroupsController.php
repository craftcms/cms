<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

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
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class UserGroupsController
{
    use ConfirmsPasswords;
    use EnforcesPermissions;
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private GeneralConfig $generalConfig,
        private UserGroups $userGroups,
    ) {
        $this->readOnly = ! $this->generalConfig->allowAdminChanges;
    }

    public function index(): Response|View
    {
        if (Edition::get() === Edition::Team) {
            return redirect()->action([self::class, 'edit'], $this->userGroups->getTeamGroup()->id);
        }

        return view('settings/users/groups/_index');
    }

    public function create(): CpScreenResponse
    {
        $crumbs = [
            ['label' => t('Settings'), 'url' => 'settings'],
            ['label' => t('Users'), 'url' => 'settings/users'],
            ['label' => t('User Groups'), 'url' => 'settings/users'],
        ];

        return new CpScreenResponse()
            ->title(t('Create a new user group'))
            ->crumbs($crumbs)
            ->addAltAction(t('Save and continue editing'), [
                'redirect' => 'settings/users/groups/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ])
            ->action('user-settings/save-group')
            ->redirectUrl('settings/users')
            ->contentTemplate('settings/users/groups/_edit.twig', [
                'group' => new UserGroup,
                'readOnly' => $this->readOnly,
            ]);
    }

    public function edit(UserGroupModel $userGroup): CpScreenResponse|View
    {
        if (Edition::get() === Edition::Team) {
            $group = $this->userGroups->getTeamGroup();

            return view('settings/users/groups/_team', [
                'group' => $group,
                'readOnly' => $this->readOnly,
            ]);
        }

        $group = $this->userGroups->getGroupById($userGroup->id);

        $crumbs = [
            ['label' => t('Settings'), 'url' => 'settings'],
            ['label' => t('Users'), 'url' => 'settings/users'],
            ['label' => t('User Groups'), 'url' => 'settings/users'],
        ];

        return new CpScreenResponse()
            ->editUrl($group->getCpEditUrl())
            ->title(trim($group->name) ?: t('Edit User Group'))
            ->crumbs($crumbs)
            ->addAltAction(t('Save and continue editing'), [
                'redirect' => 'settings/users/groups/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ])
            ->action('user-settings/save-group')
            ->redirectUrl('settings/users')
            ->contentTemplate('settings/users/groups/_edit.twig', [
                'group' => $group,
                'readOnly' => $this->readOnly,
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
            return $this->asModelFailure($group, t('Couldn’t save group.'), 'group');
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

        return $this->asModelSuccess($group, $message, 'group');
    }

    public function destroy(Request $request): Response
    {
        $groupId = $request->validate([
            'id' => ['required', 'integer'],
        ])['id'];

        $this->userGroups->deleteGroupById($groupId);

        return $this->asSuccess();
    }
}
