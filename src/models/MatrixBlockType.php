<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\FieldInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\MatrixBlock;

/**
 * MatrixBlockType model class.
 *
 * @property bool $isNew Whether this is a new block type
 * @mixin FieldLayoutBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MatrixBlockType extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Field ID
     */
    public $fieldId;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * @var bool
     */
    public $hasFieldErrors = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => MatrixBlock::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'fieldId', 'sortOrder'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * Use the block type handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle;
    }

    /**
     * Returns whether this is a new block type.
     *
     * @return bool
     */
    public function getIsNew(): bool
    {
        return (!$this->id || strpos($this->id, 'new') === 0);
    }

    /**
     * Returns the fields associated with this block type.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return $this->getFieldLayout()->getFields();
    }

    /**
     * Sets the fields associated with this block type.
     *
     * @param FieldInterface[] $fields
     *
     * @return void
     */
    public function setFields(array $fields)
    {
        $this->getFieldLayout()->setFields($fields);
    }
}
