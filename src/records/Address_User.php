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
 * Class Address_User record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property Address $address
 * @property User $user
 */
class Address_User extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['addressId'], 'unique', 'targetAttribute' => ['addressId', 'userId']],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ADDRESSES_USERS;
    }

    /**
     * Returns the user addresses user’s address.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getAddress(): ActiveQueryInterface
    {
        return $this->hasOne(Address::class, ['id' => 'addressId']);
    }

    /**
     * Returns the user addresses user’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
