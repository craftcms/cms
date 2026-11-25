<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\elements\User;
use craft\models\UserGroup;

/**
 * UserGroupUsageBlocker reports the users who will lose a user group’s permissions, and whether new users are assigned
 * to the group.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UserGroupUsageBlocker extends BaseResolvableDeletionBlocker
{
    private int $numUsers;
    private bool $isDefault;

    /**
     * Constructor
     */
    public function __construct(
        protected readonly UserGroup $group,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->numUsers = (int)User::find()->groupId($this->group->id)->status(null)->count();
        $this->isDefault = Craft::$app->getProjectConfig()->get('users.defaultGroup') === $this->group->uid;

        parent::init();
    }

    public function getKey(): string
    {
        return 'users';
    }

    public function getSummary(): string
    {
        if (!$this->numUsers) {
            return Craft::t('app', 'New users are assigned to the group.');
        }

        return Craft::t('app', '{num, number} {num, plural, =1{user is} other{users are}} in the group.', [
            'num' => $this->numUsers,
        ]);
    }

    public function getResolutionLabel(): string
    {
        if (!$this->numUsers) {
            return Craft::t('app', 'Stop assigning new users to it');
        }

        return Craft::t('app', 'Remove {num, plural, =1{user} other{users}} from the group', [
            'num' => $this->numUsers,
        ]);
    }

    protected function getResolvedMessage(): string
    {
        if (!$this->numUsers) {
            return Craft::t('app', 'New users won’t be assigned to a group once it’s deleted.');
        }

        return Craft::t('app', 'The {num, plural, =1{user} other{users}} will lose the group’s permissions once it’s deleted.', [
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
                'description' => Craft::t('app', '{count, number} {count, plural, =1{user} other{users}} will lose the group’s permissions.', [
                    'count' => $this->numUsers,
                ]),
            ];
        }

        if ($this->isDefault) {
            $items[] = [
                'label' => Craft::t('app', 'Default User Group'),
                'count' => null,
                'description' => Craft::t('app', 'New users won’t be assigned to a group.'),
            ];
        }

        return $items;
    }
}
