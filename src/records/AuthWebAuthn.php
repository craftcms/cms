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
 * Class AuthWebAuthn record.
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property string $credentialId Credential ID
 * @property string $credential Credential information
 * @property string $name The credential name
 * @property string $dateLastUsed The time when this credential was last used.
 * @property-read \yii\db\ActiveQueryInterface $user
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthWebAuthn extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userId', 'credentialId', 'credential'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::AUTH_WEBAUTHN;
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
