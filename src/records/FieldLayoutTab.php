<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Field record class.
 *
 * @property integer            $id        ID
 * @property integer            $layoutId  Layout ID
 * @property string             $name      Name
 * @property string             $sortOrder Sort order
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
    public static function tableName()
    {
        return '{{%fieldlayouttabs}}';
    }

    /**
     * Returns the field layout tab’s layout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLayout()
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'layoutId']);
    }

    /**
     * Returns the field layout tab’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields()
    {
        return $this->hasMany(FieldLayoutField::class, ['tabId' => 'id']);
    }
}
