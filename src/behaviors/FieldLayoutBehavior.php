<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\base\FieldInterface;
use craft\models\FieldLayout;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * Field Layout behavior.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutBehavior extends Behavior
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The element type that the field layout will be associated with
     */
    public $elementType;

    /**
     * @var string|null The attribute on the owner that holds the field layout ID
     */
    public $idAttribute;

    /**
     * @var int|string|callable The field layout ID, or the name of a method on the owner that will return it, or a callback function that will return it
     */
    private $_fieldLayoutId;

    /**
     * @var FieldLayout|null The field layout associated with the owner
     */
    private $_fieldLayout;

    /**
     * @var FieldInterface[]|null The fields associated with the owner's field layout
     */
    private $_fields;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws InvalidConfigException if the behavior was not configured properly
     */
    public function init()
    {
        parent::init();

        if ($this->elementType === null) {
            throw new InvalidConfigException('The element type has not been set.');
        }

        if ($this->_fieldLayoutId === null && $this->idAttribute === null) {
            $this->idAttribute = 'fieldLayoutId';
        }
    }

    /**
     * Returns the owner's field layout ID.
     *
     * @return int
     * @throws InvalidConfigException if the field layout ID could not be determined
     */
    public function getFieldLayoutId(): int
    {
        if (is_int($this->_fieldLayoutId)) {
            return $this->_fieldLayoutId;
        }

        if ($this->idAttribute !== null) {
            $id = $this->owner->{$this->idAttribute};
        } else if (is_callable($this->_fieldLayoutId)) {
            $id = call_user_func($this->_fieldLayoutId);
        } else if (is_string($this->_fieldLayoutId)) {
            $id = $this->owner->{$this->_fieldLayoutId}();
        }

        if (!isset($id) || !is_numeric($id)) {
            throw new InvalidConfigException('Unable to determine the field layout ID for '.get_class($this->owner).'.');
        }

        return $this->_fieldLayoutId = (int)$id;
    }

    /**
     * Sets the owner's field layout ID.
     *
     * @param int|string|callable $id
     */
    public function setFieldLayoutId($id)
    {
        $this->_fieldLayoutId = $id;
    }

    /**
     * Returns the owner's field layout.
     *
     * @return FieldLayout
     * @throws InvalidConfigException if the configured field layout ID is invalid
     */
    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        try {
            $id = $this->getFieldLayoutId();
        } catch (InvalidConfigException $e) {
            return $this->_fieldLayout = new FieldLayout([
                'type' => $this->elementType,
            ]);
        }

        if (($fieldLayout = Craft::$app->getFields()->getLayoutById($id)) === null) {
            throw new InvalidConfigException('Invalid field layout ID: '.$id);
        }

        return $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Sets the owner's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(FieldLayout $fieldLayout)
    {
        $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Returns the fields associated with the owner's field layout.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        try {
            $id = $this->getFieldLayoutId();
        } catch (InvalidConfigException $e) {
            return [];
        }

        return $this->_fields = Craft::$app->getFields()->getFieldsByLayoutId($id);
    }

    /**
     * Sets the fields associated with the owner's field layout
     *
     * @param FieldInterface[] $fields
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;
    }
}
