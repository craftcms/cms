<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Field record class.
 *
 * @property int                $id        ID
 * @property int                $layoutId  Layout ID
 * @property string             $name      Name
 * @property int                $sortOrder Sort order
 * @property FieldLayout        $layout    Layout
 * @property FieldLayoutField[] $fields    Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldLayoutTab extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%fieldlayouttabs}}';
    }

    /**
     * Returns the field layout tab’s layout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'layoutId']);
    }

    /**
     * Returns the field layout tab’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields(): ActiveQueryInterface
    {
        return $this->hasMany(FieldLayoutField::class, ['tabId' => 'id']);
    }
}
