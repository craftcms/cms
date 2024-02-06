<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use craft\helpers\Json;

/**
 * Class GqlSchema record.
 *
 * @property int $id ID
 * @property string $name Schema name
 * @property array $scope The scope of the schema.
 * @property bool $isPublic Whether this schema is public
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GqlSchema extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::GQLSCHEMAS;
    }

    public function __construct($config = [])
    {
        if (is_string($config['scope'] ?? null)) {
            $config['scope'] = Json::decode($config['scope']);
        }

        parent::__construct($config);
    }
}
