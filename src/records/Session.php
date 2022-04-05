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
 * Class Session record.
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property string $token Token
 * @property User $user User
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Session extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['token'], 'required'],
            [['token'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::SESSIONS;
    }

    /**
     * Returns the session’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
