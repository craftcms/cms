<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

/**
 * Reports the users who will lose a user group’s permissions, and whether new users are assigned to the group.
 *
 * @since 6.0.0
 */
class UserGroupUsageBlocker extends BaseResolvableDeletionBlocker
{
    private readonly int $numUsers;

    private readonly bool $isDefault;

    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly UserGroup $group,
        array $config = [],
    ) {
        parent::__construct($config);

        $this->numUsers = User::find()->groupId($this->group->id)->status(null)->count();
        $this->isDefault = ProjectConfig::get('users.defaultGroup') === $this->group->uid;
    }

    public function getKey(): string
    {
        return 'users';
    }

    public function getSummary(): string
    {
        if (! $this->numUsers) {
            return t('New users are assigned to the group.');
        }

        return t('{num, number} {num, plural, =1{user is} other{users are}} in the group.', [
            'num' => $this->numUsers,
        ]);
    }

    public function getResolutionLabel(): string
    {
        if (! $this->numUsers) {
            return t('Stop assigning new users to it');
        }

        return t('Remove {num, plural, =1{user} other{users}} from the group', [
            'num' => $this->numUsers,
        ]);
    }

    protected function getResolvedMessage(): string
    {
        if (! $this->numUsers) {
            return t('New users won’t be assigned to a group once it’s deleted.');
        }

        return t('The {num, plural, =1{user} other{users}} will lose the group’s permissions once it’s deleted.', [
            'num' => $this->numUsers,
        ]);
    }

    protected function defineItems(): array
    {
        $items = [];

        if ($this->numUsers) {
            $items[] = [
                'label' => User::pluralDisplayName(),
                'count' => $this->numUsers,
                'description' => t('{count, number} {count, plural, =1{user} other{users}} will lose the group’s permissions.', [
                    'count' => $this->numUsers,
                ]),
            ];
        }

        if ($this->isDefault) {
            $items[] = [
                'label' => t('Default User Group'),
                'count' => null,
                'description' => t('New users won’t be assigned to a group.'),
            ];
        }

        return $items;
    }
}
