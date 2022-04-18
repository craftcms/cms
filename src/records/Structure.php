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
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class Structure record.
 *
 * @property int $id ID
 * @property int $maxLevels Max levels
 * @property StructureElement[] $elements Elements
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Structure extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['maxLevels'], 'number', 'min' => 1, 'max' => 65535, 'integerOnly' => true],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::STRUCTURES;
    }

    /**
     * Returns the structure’s elements.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElements(): ActiveQueryInterface
    {
        return $this->hasMany(StructureElement::class, ['structureId' => 'id']);
    }
}
