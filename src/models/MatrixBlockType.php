<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\FieldInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutTrait;

/**
 * MatrixBlockType model class.
 *
 * @property boolean $isNew Whether this is a new block type
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MatrixBlockType extends Model
{
    // Traits
    // =========================================================================

    use FieldLayoutTrait;

    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Field ID
     */
    public $fieldId;

    /**
     * @var string Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var integer Sort order
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
                'class' => \craft\behaviors\FieldLayoutBehavior::class,
                'elementType' => \craft\elements\MatrixBlock::class
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
    public function __toString()
    {
        return $this->handle;
    }

    /**
     * Returns whether this is a new block type.
     *
     * @return boolean
     */
    public function getIsNew()
    {
        return (!$this->id || strncmp($this->id, 'new', 3) === 0);
    }

    /**
     * Returns the fields associated with this block type.
     *
     * @return FieldInterface[]
     */
    public function getFields()
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
    public function setFields($fields)
    {
        $this->getFieldLayout()->setFields($fields);
    }
}
