<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Data\UserGroup;

use function CraftCms\Cms\currentUser;

class UserGroupSelect extends Combobox
{
    #[\Override]
    protected mixed $value = [];

    public function component(): string
    {
        return 'craft:user-group-select';
    }

    /** @return list<string> */
    public function emptyValue(): array
    {
        return [];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $groups = UserGroups::getAllGroups()
            ->filter(fn (UserGroup $group): bool => $group->id !== null && $group->uid !== null)
            ->values();

        $this
            ->multiple()
            ->requireOptionMatch()
            ->showAllOnEmpty()
            ->options($groups->map(fn (UserGroup $group): array => [
                'label' => $group->getUiLabel(),
                'value' => $group->uid,
            ])->all());

        return [
            ...parent::props($value),
            'canCreate' => currentUser()?->isAdmin()
                && Cms::config()->allowAdminChanges
                && Edition::get()->value >= Edition::Pro->value,
            'groups' => $groups->map(fn (UserGroup $group): array => [
                'id' => $group->id,
                'uid' => $group->uid,
                'name' => $group->name,
                'handle' => $group->handle,
                'description' => $group->description,
            ])->all(),
        ];
    }
}
