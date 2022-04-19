<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\records\UserGroup as UserGroupRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * UserGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserGroup extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null Description
     * @since 3.5.0
     */
    public ?string $description = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
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
            ],
        ];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => UserGroupRecord::class];
        return $rules;
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }

    /**
     * Returns whether the group has permission to perform a given action.
     *
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        if ($this->id) {
            return Craft::$app->getUserPermissions()->doesGroupHavePermission($this->id, $permission);
        }

        return false;
    }

    /**
     * Returns the user group’s config.
     *
     * @param bool $withPermissions Whether permissions should be included
     * @return array
     * @since 3.5.0
     */
    public function getConfig(bool $withPermissions = true): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
        ];

        if ($withPermissions && $this->id) {
            $permissions = Craft::$app->getUserPermissions()->getPermissionsByGroupId($this->id);
            sort($permissions);
            $config['permissions'] = $permissions;
        }

        return $config;
    }
}
