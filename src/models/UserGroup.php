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
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public function attributeLabels()
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
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
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
}
