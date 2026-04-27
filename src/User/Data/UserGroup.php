<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Describable;
use CraftCms\Cms\Component\Contracts\Grippable;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Stringable;

use function CraftCms\Cms\t;

class UserGroup extends Component implements Actionable, Chippable, CpEditable, Describable, Grippable, Stringable
{
    #[AllowedInSandbox]
    public ?int $id = null;

    #[AllowedInSandbox]
    public ?string $name = null;

    #[AllowedInSandbox]
    public ?string $handle = null;

    #[AllowedInSandbox]
    public ?string $description = null;

    public ?string $uid = null;

    public static function get(int|string $id): ?self
    {
        return UserGroups::getGroupById($id);
    }

    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return "settings/users/groups/$this->id";
    }

    public function getActionMenuItems(): array
    {
        $items = [];

        if (
            $this->id &&
            Auth::user()?->isAdmin() &&
            Cms::config()->allowAdminChanges
        ) {
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'gear',
                'label' => t('User group settings'),
            ];

            HtmlStack::jsWithVars(fn ($id, $params) => <<<JS
$('#' + $id).on('click', () => {
  new Craft.CpScreenSlideout('user-settings/edit-group', {
    params: $params,
  })
});
JS, [
                InputNamespace::namespaceId($editId),
                ['groupId' => $this->id],
            ]);
        }

        return $items;
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', Rule::unique(Table::USERGROUPS, 'name')->ignore($this->id)],
            'handle' => ['required', 'string', 'max:255', new HandleRule(reservedWords: [
                'admins',
                'all',
                'credentialed',
                'dateCreated',
                'dateUpdated',
                'id',
                'inactive',
                'new',
                'title',
                'uid',
            ]), Rule::unique(Table::USERGROUPS, 'handle')->ignore($this->id)],
        ];
    }

    /**
     * Use the translated group name as the string representation.
     */
    public function __toString(): string
    {
        return t($this->name, category: 'site') ?: self::class;
    }

    /**
     * Returns whether the group has permission to perform a given action.
     */
    public function can(string $permission): bool
    {
        if ($this->id) {
            return UserPermissions::doesGroupHavePermission($this->id, $permission);
        }

        return false;
    }

    /**
     * Returns the user group’s config.
     *
     * @param  bool  $withPermissions  Whether permissions should be included
     */
    public function getConfig(bool $withPermissions = true): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description ?: null,
        ];

        if ($withPermissions && $this->id) {
            $config['permissions'] = UserPermissions::getPermissionsByGroupId($this->id)->sort()->all();
        }

        return $config;
    }
}
