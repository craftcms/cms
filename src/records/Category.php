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
 * Category record.
 *
 * @property integer       $id      ID
 * @property integer       $groupId Group ID
 * @property Element       $element Element
 * @property CategoryGroup $group   Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Category extends ActiveRecord
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
        return '{{%categories}}';
    }

    /**
     * Returns the category’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the category’s group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup()
    {
        return $this->hasOne(CategoryGroup::class, ['id' => 'groupId']);
    }
}
