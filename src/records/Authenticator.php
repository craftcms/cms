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
 * @property int $mfaSecret Folder ID
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
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
            [['mfaCode'], 'string', 'max' => 32],
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
