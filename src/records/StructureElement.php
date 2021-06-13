<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use Craft;
use craft\db\ActiveRecord;
use craft\db\StructuredElementQuery;
use craft\db\Table;
use creocoder\nestedsets\NestedSetsBehavior;
use yii\db\ActiveQueryInterface;

/**
 * Class StructureElement record.
 *
 * @property int $id ID
 * @property int $structureId Structure ID
 * @property int $elementId Element ID
 * @property int $root Root
 * @property int $lft Lft
 * @property int $rgt Rgt
 * @property int $level Level
 * @property Structure $structure Structure
 * @property Element $element Element
 * @mixin NestedSetsBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class StructureElement extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['root', 'lft', 'rgt'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
            [['level'], 'number', 'min' => 0, 'max' => 65535, 'integerOnly' => true],
            [['structureId'], 'unique', 'targetAttribute' => ['structureId', 'elementId']],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::STRUCTUREELEMENTS;
    }

    /**
     * @inheritdoc
     * @return StructuredElementQuery
     */
    public static function find(): StructuredElementQuery
    {
        return Craft::createObject(StructuredElementQuery::class, [static::class]);
    }

    /**
     * Returns the structure element’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure(): ActiveQueryInterface
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    /**
     * Returns the structure element’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['tree'] = [
            'class' => NestedSetsBehavior::class,
            'treeAttribute' => 'root',
            'leftAttribute' => 'lft',
            'rightAttribute' => 'rgt',
            'depthAttribute' => 'level',
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
}
