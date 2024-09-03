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
 * Class Authenticator record.
 *
 * @property int $id ID
 * @property int $userId Volume ID
 * @property string $auth2faSecret 2FA secret
 * @property int $oldTimestamp old TOTP timestamp
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Authenticator extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::AUTHENTICATOR;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['userId', 'auth2faSecret'], 'required'],
            [['auth2faSecret'], 'string', 'max' => 32],
            [['oldTimestamp'], 'integer'],
        ];
    }

    /**
     * Returns the User element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
