<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Field layout record class.
 *
 * @property int $id ID
 * @property string $type Type
 * @property FieldLayoutTab[] $tabs Tabs
 * @property FieldLayoutField[] $fields Fields
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDLAYOUTS;
    }

    /**
     * Returns the field layout’s tabs.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getTabs(): ActiveQueryInterface
    {
        return $this->hasMany(FieldLayoutTab::class, ['layoutId' => 'id']);
    }

    /**
     * Returns the field layout’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields(): ActiveQueryInterface
    {
        return $this->hasMany(FieldLayoutField::class, ['layoutId' => 'id']);
    }
}
