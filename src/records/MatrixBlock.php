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
 * Class MatrixBlock record.
 *
 * @property integer         $id          ID
 * @property integer         $ownerId     Owner ID
 * @property Locale          $ownerLocale Owner locale
 * @property integer         $fieldId     Field ID
 * @property integer         $typeId      Type ID
 * @property string          $sortOrder   Sort order
 * @property Element         $element     Element
 * @property Element         $owner       Owner
 * @property Field           $field       Field
 * @property MatrixBlockType $type        Type
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MatrixBlock extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ownerLocale'], 'craft\\app\\validators\\Locale'],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%matrixblocks}}';
    }

    /**
     * Returns the matrix block’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::className(), ['id' => 'id']);
    }

    /**
     * Returns the matrix block’s owner.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwner()
    {
        return $this->hasOne(Element::className(), ['id' => 'ownerId']);
    }

    /**
     * Returns the matrix block’s ownerLocale.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwnerLocale()
    {
        return $this->hasOne(Locale::className(), ['id' => 'ownerLocale']);
    }

    /**
     * Returns the matrix block’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField()
    {
        return $this->hasOne(Field::className(), ['id' => 'fieldId']);
    }

    /**
     * Returns the matrix block’s type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType()
    {
        return $this->hasOne(MatrixBlockType::className(), ['id' => 'typeId']);
    }
}
