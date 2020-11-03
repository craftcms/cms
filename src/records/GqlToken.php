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
 * @property int $schemaId Schema ID
 * @property string $name Token name
 * @property string $accessToken The access token
 * @property bool $enabled whether the token is enabled
 * @property \DateTime $expiryDate Expiration Date
 * @property \DateTime $lastUsed When the schema was last used
 * @property GqlSchema $scope Scope
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class GqlToken extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::GQLTOKENS;
    }

    /**
     * Returns the token's schema.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSchema(): ActiveQueryInterface
    {
        return $this->hasOne(GqlSchema::class, ['id' => 'schemaId']);
    }
}
