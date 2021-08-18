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
 * Class FieldLayoutField record.
 *
 * @property int $id ID
 * @property int $layoutId Layout ID
 * @property int $tabId Tab ID
 * @property int $fieldId Field ID
 * @property bool $required Required
 * @property int $sortOrder Sort order
 * @property FieldLayout $layout Layout
 * @property FieldLayoutTab $tab Tab
 * @property Field $field Field
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayoutField extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['layoutId'], 'unique', 'targetAttribute' => ['layoutId', 'fieldId']],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDLAYOUTFIELDS;
    }

    /**
     * Returns the field layout field’s layout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'layoutId']);
    }

    /**
     * Returns the field layout field’s tab.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getTab(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayoutTab::class, ['id' => 'tabId']);
    }

    /**
     * Returns the field layout field’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }
}
