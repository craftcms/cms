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
 * @property string $credentialId Credential ID
 * @property string $credential Credential JSON
 * @property string $credentialName Name of the credential
 * @property string $dateLastUsed Date last used
 * @property string $uid uid
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class WebAuthn extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::WEBAUTHN;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['userId', 'credentialId', 'credential'], 'required'],
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
