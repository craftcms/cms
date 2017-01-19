<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\fields\Matrix;

/**
 * MatrixSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MatrixSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Max blocks
     */
    public $maxBlocks;


    /**
     * @var Matrix|null
     */
    private $_matrixField;

    /**
     * @var
     */
    private $_blockTypes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['maxBlocks'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * Constructor
     *
     * @param Matrix|null $matrixField
     */
    public function __construct(Matrix $matrixField = null)
    {
        $this->_matrixField = $matrixField;

        parent::__construct();
    }

    /**
     * Returns the field associated with this.
     *
     * @return Matrix|null
     */
    public function getField()
    {
        return $this->_matrixField;
    }

    /**
     * Returns the block types.
     *
     * @return MatrixBlockType[]
     */
    public function getBlockTypes(): array
    {
        if ($this->_blockTypes !== null) {
            return $this->_blockTypes;
        }

        if (empty($this->_matrixField->id)) {
            return [];
        }

        return $this->_blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->_matrixField->id);
    }

    /**
     * Sets the block types.
     *
     * @param MatrixBlockType[] $blockTypes
     *
     * @return void
     */
    public function setBlockTypes(array $blockTypes)
    {
        $this->_blockTypes = $blockTypes;
    }

    /**
     * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
     * logged to the `storage/logs/` folder as a warning.
     *
     * In addition, we validate the block type settings.
     *
     * @param array|null $attributeNames
     * @param bool       $clearErrors
     *
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        // Enforce $clearErrors without copying code if we don't have to
        $validates = parent::validate($attributeNames, $clearErrors);

        if (!Craft::$app->getMatrix()->validateFieldSettings($this->getField())) {
            $validates = false;
        }

        return $validates;
    }
}
