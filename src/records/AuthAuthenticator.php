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
 * Class VolumeFolder record.
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property string $authenticatorSecret Authenticator secret
 * @property int $authenticatorTimestamp Timestamp for the last used code
 * @property-read \yii\db\ActiveQueryInterface $user
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthAuthenticator extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userId'], 'unique'],
            [['userId', 'authenticatorSecret', 'authenticatorTimestamp'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::AUTH_AUTHENTICATOR;
    }

    /**
     * Returns the owner of this authenticator.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
