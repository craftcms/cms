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
 * Class FieldGroup record.
 *
 * @property integer $id     ID
 * @property string  $name   Name
 * @property Field[] $fields Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldGroup extends ActiveRecord
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
        return '{{%fieldgroups}}';
    }

    /**
     * Returns the field group’s fields.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFields()
    {
        return $this->hasMany(Field::class, ['groupId' => 'id']);
    }
}
