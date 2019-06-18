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
 * Class GqlToken record.
 *
 * @property int $id ID
 * @property string $name Token name
 * @property string $accessToken The access Token
 * @property bool $enabled whether the token is enabled
 * @property \DateTime $expiryDate Expiration Date
 * @property \DateTime $lastUsed When the token was last used
 * @property array $permissions The permissions the token has.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GqlToken extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::GQLTOKENS;
    }
}
