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
 * Class EntryType record.
 *
 * @property int $id ID
 * @property int $sectionId Section ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property bool $hasTitleField Has title field
 * @property string $titleLabel Title label
 * @property string $titleFormat Title format
 * @property int $sortOrder Sort order
 * @property Section $section Section
 * @property FieldLayout $fieldLayout Field layout
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends ActiveRecord
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
        return Table::ENTRYTYPES;
    }

    /**
     * Returns the entry type’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection(): ActiveQueryInterface
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry type’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
