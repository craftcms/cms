<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;

/**
 * Class GqlSchema record.
 *
 * @property int $id ID
 * @property string $name Schema name
 * @property string $accessToken The access Token
 * @property bool $enabled whether the schema is enabled
 * @property \DateTime $expiryDate Expiration Date
 * @property \DateTime $lastUsed When the schema was last used
 * @property array $scope The scope of the schema has.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GqlSchema extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::GQLSCHEMAS;
    }
}
