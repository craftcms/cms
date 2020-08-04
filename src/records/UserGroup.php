<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Class UserGroup record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string|null $description
 * @property User[] $users Users
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserGroup extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::USERGROUPS;
    }

    /**
     * Returns the group’s users.
     *
     * @return ActiveQueryInterface
     */
    public function getUsers(): ActiveQueryInterface
    {
        return $this->hasMany(User::class, ['id' => 'userId'])
            ->viaTable(Table::USERGROUPS_USERS, ['groupId' => 'id']);
    }
}
