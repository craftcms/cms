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
 * Class CategoryGroup record.
 *
 * @property integer                      $id            ID
 * @property integer                      $structureId   Structure ID
 * @property integer                      $fieldLayoutId Field layout ID
 * @property string                       $name          Name
 * @property string                       $handle        Handle
 * @property Structure                    $structure     Structure
 * @property FieldLayout                  $fieldLayout   Field layout
 * @property CategoryGroup_SiteSettings[] $siteSettings  Site settings
 * @property Category[]                   $categories    Categories
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryGroup extends ActiveRecord
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
        return '{{%categorygroups}}';
    }

    /**
     * Returns the category group’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure()
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    /**
     * Returns the category group’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout()
    {
        return $this->hasOne(FieldLayout::class,
            ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the category group’s site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSiteSettings()
    {
        return $this->hasMany(CategoryGroup_SiteSettings::class, ['groupId' => 'id']);
    }

    /**
     * Returns the category group’s categories.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['groupId' => 'id']);
    }
}
