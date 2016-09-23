<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\validators\SiteIdValidator;

/**
 * Class MatrixBlock record.
 *
 * @property integer         $id          ID
 * @property integer         $ownerId     Owner ID
 * @property integer         $ownerSiteId Owner site ID
 * @property integer         $fieldId     Field ID
 * @property integer         $typeId      Type ID
 * @property string          $sortOrder   Sort order
 * @property Element         $element     Element
 * @property Element         $owner       Owner
 * @property Site            $ownerSite   Owner's site
 * @property Field           $field       Field
 * @property MatrixBlockType $type        Type
 * @property Site            $site        Site
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
            [['ownerSiteId'], SiteIdValidator::class],
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
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the matrix block’s owner.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwner()
    {
        return $this->hasOne(Element::class, ['id' => 'ownerId']);
    }

    /**
     * Returns the matrix block’s owner's site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwnerSite()
    {
        return $this->hasOne(Site::class, ['id' => 'ownerSiteId']);
    }

    /**
     * Returns the matrix block’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField()
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    /**
     * Returns the matrix block’s type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType()
    {
        return $this->hasOne(MatrixBlockType::class, ['id' => 'typeId']);
    }
}
