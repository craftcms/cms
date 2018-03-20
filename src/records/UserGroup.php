<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class UserGroup record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property User[] $users Users
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroup extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%usergroups}}';
    }

    /**
     * Returns the group’s users.
     *
     * @return ActiveQueryInterface
     */
    public function getUsers(): ActiveQueryInterface
    {
        return $this->hasMany(User::class, ['id' => 'userId'])
            ->viaTable('{{%usergroups_users}}', ['groupId' => 'id']);
    }
}
