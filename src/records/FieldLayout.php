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
 * Field layout record class.
 *
 * @property integer            $id     ID
 * @property string             $type   Type
 * @property FieldLayoutTab[]   $tabs   Tabs
 * @property FieldLayoutField[] $fields Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldLayout extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%fieldlayouts}}';
    }

    /**
     * Returns the field layout’s tabs.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getTabs()
    {
        return $this->hasMany(FieldLayoutTab::class, ['layoutId' => 'id']);
    }

    /**
     * Returns the field layout’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields()
    {
        return $this->hasMany(FieldLayoutField::class, ['layoutId' => 'id']);
    }
}
