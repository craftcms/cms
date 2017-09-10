<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class PluginStoreToken record.
 *
 * @property int    $id             ID
 * @property int    $userId         User ID
 * @property int    $oauthTokenId   OAuth token ID
 * @property User   $user           User
 * @property User   $oauthToken     OAuth token
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginStoreToken extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%pluginstoretokens}}';
    }

    /**
     * Returns the token’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * Returns the token’s OAuth token.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOauthToken(): ActiveQueryInterface
    {
        return $this->hasOne(OAuthToken::class, ['id' => 'oauthTokenId']);
    }
}
