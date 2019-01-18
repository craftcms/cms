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
 * Class CategoryGroup record.
 *
 * @property int $id ID
 * @property int $structureId Structure ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property Structure $structure Structure
 * @property FieldLayout $fieldLayout Field layout
 * @property CategoryGroup_SiteSettings[] $siteSettings Site settings
 * @property Category[] $categories Categories
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends ActiveRecord
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
        return Table::CATEGORYGROUPS;
    }

    /**
     * Returns the category group’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure(): ActiveQueryInterface
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    /**
     * Returns the category group’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class,
            ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the category group’s site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSiteSettings(): ActiveQueryInterface
    {
        return $this->hasMany(CategoryGroup_SiteSettings::class, ['groupId' => 'id']);
    }

    /**
     * Returns the category group’s categories.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCategories(): ActiveQueryInterface
    {
        return $this->hasMany(Category::class, ['groupId' => 'id']);
    }
}
