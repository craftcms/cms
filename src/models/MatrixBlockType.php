<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\GqlInlineFragmentInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use yii\base\InvalidConfigException;

/**
 * MatrixBlockType model class.
 *
 * @property bool $isNew Whether this is a new block type
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockType extends Model implements GqlInlineFragmentInterface
{
    // Properties
    // =========================================================================

    /**
     * @var int|string|null ID The block ID. If unsaved, it will be in the format "newX".
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

    /**
     * @var string|mixed
     */
    public $uid;

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
        $rules = parent::rules();
        $rules[] = [['id', 'fieldId', 'sortOrder'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Use the block type handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle ?: static::class;
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
     * Returns the block type's field.
     *
     * @return Matrix
     * @throws InvalidConfigException if [[fieldId]] is missing or invalid
     * @since 3.3.0
     */
    public function getField(): Matrix
    {
        if ($this->fieldId === null) {
            throw new InvalidConfigException('Block type missing its field ID');
        }

        /** @var Matrix $field */
        if (($field = Craft::$app->getFields()->getFieldById($this->fieldId)) === null) {
            throw new InvalidConfigException('Invalid field ID: ' . $this->fieldId);
        }

        return $field;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getFieldContext(): string
    {
        return 'matrixBlockType:' . $this->uid;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingPrefix(): string
    {
        return $this->handle;
    }
}
