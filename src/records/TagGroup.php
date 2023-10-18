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
use DateTime;
use yii\db\ActiveQueryInterface;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class TagGroup record.
 *
 * @property int $id ID
 * @property int|null $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property DateTime|string|null $dateDeleted Date deleted
 * @property FieldLayout $fieldLayout Field layout
 * @property Tag[] $tags Tags
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TagGroup extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::TAGGROUPS;
    }

    /**
     * Returns the tag group’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class,
            ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the tag group’s tags.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getTags(): ActiveQueryInterface
    {
        return $this->hasMany(Tag::class, ['groupId' => 'id']);
    }
}
