<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\validators\HandleValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class UserGroup record.
 *
 * @property integer $id     ID
 * @property string  $name   Name
 * @property string  $handle Handle
 * @property User[]  $users  Users
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserGroup extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%usergroups}}';
    }

    /**
     * Returns the group’s users.
     *
     * @return ActiveQueryInterface
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'userId'])
            ->viaTable('{{%usergroups_users}}', ['groupId' => 'id']);
    }
}
