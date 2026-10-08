<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\PermissionTree;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use CraftCms\Cms\User\UserGroups;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class UserGroupsController extends BaseUserSettingsController
{
    use ConfirmsPasswords;
    use EnforcesPermissions;
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly UserGroups $userGroups,
        private readonly UiResolver $uiResolver,
    ) {
        $this->readOnly = ! $this->generalConfig->allowAdminChanges;
    }

    public function index(): RedirectResponse|\Inertia\Response
    {
        if (Edition::get() === Edition::Team) {
            return redirect()->action([self::class, 'edit'], $this->userGroups->getTeamGroup()->id);
        }

        return Inertia::render('settings/users/groups/Index', [
            'crumbs' => $this->crumbs(),
            'title' => t('User Settings'),
            'subnav' => $this->subnav(),
            'groups' => $this->userGroups->getAllGroups(),
        ]);
    }

    public function create(UserPermissions $userPermissions): CpScreenResponse
    {
        $group = new UserGroup;

        return new CpScreenResponse()
            ->title(t('Create a new user group'))
            ->crumbs([
                ...$this->crumbs(),
                new ActionItem()->label(t('User Groups'))->href(cp_url('settings/users')),
                new ActionItem()->label(t('New user group')),
            ])
            ->redirectUrl('settings/users')
            ->inertiaPage('settings/users/groups/Edit', [
                'ui' => $this->ui($group, $userPermissions, true),
                'submit' => $this->submit(),
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
            ->crumbs([
                ...$this->crumbs(),
                new ActionItem()->label(t('User Groups'))->href(cp_url('settings/users')),
                new ActionItem()->label($group->name),
            ])
            ->redirectUrl('settings/users')
            ->inertiaPage('settings/users/groups/Edit', [
                'ui' => $this->ui($group, $userPermissions),
                'submit' => $this->submit(),
                'elevatedFields' => ['permissions'],
                'deleteAction' => $this->readOnly ? null : [
                    'label' => t('Delete group'),
                    'confirm' => t('Are you sure you want to delete “{name}”?', [
                        'name' => $group->name,
                    ]),
                    'url' => action([self::class, 'destroy'], ['groupId' => $group->id]),
                ],
            ])
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

        $isNewGroup = ! $group->id;
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

        $group->name = $userGroupData->name;
        $group->handle = $userGroupData->handle;
        $group->description = $userGroupData->description;

        if (! $this->userGroups->saveGroup($group)) {
            throw ValidationException::withMessages($group->errors()->getMessages());
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

        return $this->asSuccess($message, ['group' => [
            'id' => $group->id,
            'uid' => $group->uid,
            'name' => $group->name,
            'handle' => $group->handle,
            'description' => $group->description,
        ]]);
    }

    public function destroy(Request $request, int $groupId): Response
    {
        $this->userGroups->deleteGroupById($groupId);

        return $this->asSuccess(t('Group deleted.'), redirect: route('craft.cp.settings.users.index'));
    }

    private function ui(UserGroup $group, UserPermissions $userPermissions, bool $brandNew = false): UiPayload
    {
        $handle = Handle::make('handle');
        $values = $group->getConfig(true);

        if ($brandNew) {
            $handle->source('name');
        }

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('id'),
            Field::make(t('Name'), Text::make('name')->autofocus())->required(),
            Field::make(t('Handle'), $handle)->required(),
            Field::make(t('Description'), Textarea::make('description')),
            Separator::make('permissions-separator'),
            Heading::make('permissions-heading', t('Permissions')),
            Field::make(null, PermissionTree::make('permissions')
                ->ariaLabel(t('Permissions'))
                ->groups($userPermissions->getAllPermissions())),
        ]), new UiContext(
            values: [
                'id' => $group->id,
                ...$values,
                'description' => $group->description ?? '',
                'permissions' => $values['permissions'] ?? [],
            ],
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
        ));
    }

    /** @return array{method: 'post', url: string} */
    private function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([self::class, 'store']),
        ];
    }
}
