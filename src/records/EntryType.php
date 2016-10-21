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
 * Class EntryType record.
 *
 * @property integer     $id            ID
 * @property integer     $sectionId     Section ID
 * @property integer     $fieldLayoutId Field layout ID
 * @property string      $name          Name
 * @property string      $handle        Handle
 * @property boolean     $hasTitleField Has title field
 * @property string      $titleLabel    Title label
 * @property string      $titleFormat   Title format
 * @property integer     $sortOrder     Sort order
 * @property Section     $section       Section
 * @property FieldLayout $fieldLayout   Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryType extends ActiveRecord
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
        return '{{%entrytypes}}';
    }

    /**
     * Returns the entry type’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection()
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry type’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout()
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
