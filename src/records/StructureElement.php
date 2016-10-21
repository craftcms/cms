<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use craft\app\db\NestedSetsTrait;
use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;
use craft\app\db\StructuredElementQuery;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class StructureElement record.
 *
 * @property integer   $id          ID
 * @property integer   $structureId Structure ID
 * @property integer   $elementId   Element ID
 * @property integer   $root        Root
 * @property integer   $lft         Lft
 * @property integer   $rgt         Rgt
 * @property integer   $level       Level
 * @property Structure $structure   Structure
 * @property Element   $element     Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class StructureElement extends ActiveRecord
{
    // Traits
    // =========================================================================

    use NestedSetsTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['root'],
                'number',
                'min' => 0,
                'max' => 4294967295,
                'integerOnly' => true
            ],
            [
                ['lft'],
                'number',
                'min' => 0,
                'max' => 4294967295,
                'integerOnly' => true
            ],
            [
                ['rgt'],
                'number',
                'min' => 0,
                'max' => 4294967295,
                'integerOnly' => true
            ],
            [
                ['level'],
                'number',
                'min' => 0,
                'max' => 65535,
                'integerOnly' => true
            ],
            [
                ['structureId'],
                'unique',
                'targetAttribute' => ['structureId', 'elementId']
            ],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%structureelements}}';
    }

    /**
     * @inheritdoc
     *
     * @return StructuredElementQuery
     */
    public static function find()
    {
        /** @var StructuredElementQuery $query */
        $query = Craft::createObject(StructuredElementQuery::class, [get_called_class()]);

        return $query;
    }

    /**
     * Returns the structure element’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure()
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    /**
     * Returns the structure element’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::class,
                'treeAttribute' => 'root',
                'leftAttribute' => 'lft',
                'rightAttribute' => 'rgt',
                'depthAttribute' => 'level',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            static::SCENARIO_DEFAULT => static::OP_ALL,
        ];
    }
}
