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
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * MatrixBlockType model class.
 *
 * @property bool $isNew Whether this is a new block type
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MatrixBlockType extends Model implements GqlInlineFragmentInterface
{
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

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => MatrixBlock::class,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
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

    /**
     * Returns the field layout config for this block type.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $field = $this->getField();

        $config = [
            'field' => $field->uid,
            'name' => $this->name,
            'handle' => $this->handle,
            'sortOrder' => (int)$this->sortOrder,
            'fields' => [],
        ];

        if (
            ($fieldLayout = $this->getFieldLayout()) &&
            ($fieldLayoutConfig = $fieldLayout->getConfig())
        ) {
            if (!$fieldLayout->uid) {
                $fieldLayout->uid = StringHelper::UUID();
            }
            $config['fieldLayouts'][$fieldLayout->uid] = $fieldLayoutConfig;
        }

        $fieldsService = Craft::$app->getFields();
        foreach ($this->getFields() as $field) {
            $config['fields'][$field->uid] = $fieldsService->createFieldConfig($field);
        }

        return $config;
    }
}
