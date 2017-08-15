<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use DateTime;

/**
 * Class OauthToken record.
 *
 * @property int      $id
 * @property int      $userId
 * @property string   $accessToken
 * @property string   $tokenType
 * @property string   $expiresIn
 * @property string   $refreshToken
 * @property DateTime $expiryDate
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string   $uid
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class OauthToken extends ActiveRecord
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
        return '{{%oauth_tokens}}';
    }
}
