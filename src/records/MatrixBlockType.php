<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\validators\HandleValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class MatrixBlockType record.
 *
 * @property int $id ID
 * @property int $fieldId Field ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property int $sortOrder Sort order
 * @property Field $field Field
 * @property FieldLayout $fieldLayout Field layout
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockType extends ActiveRecord
{
    // Properties
    // =========================================================================

    /**
     * Whether the Name and Handle attributes should validated to ensure they’re unique.
     *
     * @var bool
     */
    public $validateUniques = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'unique', 'targetAttribute' => ['name', 'fieldId']],
            [['handle'], 'unique', 'targetAttribute' => ['handle', 'fieldId']],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'id',
                    'dateCreated',
                    'dateUpdated',
                    'uid',
                    'title'
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%matrixblocktypes}}';
    }

    /**
     * Returns the matrix block type’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    /**
     * Returns the matrix block type’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
