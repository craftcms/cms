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
 * Class FieldGroup record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property Field[] $fields Fields
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldGroup extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDGROUPS;
    }

    /**
     * Returns the field group’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields(): ActiveQueryInterface
    {
        return $this->hasMany(Field::class, ['groupId' => 'id']);
    }
}
