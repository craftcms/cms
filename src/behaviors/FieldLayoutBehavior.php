<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\FieldLayoutProviderInterface;
use craft\models\EntryType;
use craft\models\FieldLayout;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * Field Layout behavior.
 *
 * @property FieldLayout $fieldLayout
 * @property ElementInterface|Model $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayoutBehavior extends Behavior
{
    /**
     * @var class-string<ElementInterface>|null The element type that the field layout will be associated with
     */
    public ?string $elementType = null;

    /**
     * @var string|null The attribute on the owner that holds the field layout ID
     */
    public ?string $idAttribute = null;

    /**
     * @var int|string|callable|null The field layout ID, or the name of a method on the owner that will return it, or a callback function that will return it
     */
    private $_fieldLayoutId;

    /**
     * @var FieldLayout|null The field layout associated with the owner
     */
    private ?FieldLayout $_fieldLayout = null;

    /**
     * @inheritdoc
     * @throws InvalidConfigException if the behavior was not configured properly
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->elementType)) {
            throw new InvalidConfigException('The element type has not been set.');
        }

        if (!isset($this->_fieldLayoutId) && !isset($this->idAttribute)) {
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

        if (isset($this->idAttribute)) {
            $id = $this->owner->{$this->idAttribute};
        } elseif (is_callable($this->_fieldLayoutId)) {
            $id = call_user_func($this->_fieldLayoutId);
        } elseif (is_string($this->_fieldLayoutId)) {
            $id = $this->owner->{$this->_fieldLayoutId}();
        }

        if (!isset($id) || !is_numeric($id)) {
            throw new InvalidConfigException('Unable to determine the field layout ID for ' . get_class($this->owner) . '.');
        }

        return $this->_fieldLayoutId = (int)$id;
    }

    /**
     * Sets the owner's field layout ID.
     *
     * @param callable|int|string $id
     */
    public function setFieldLayoutId(callable|int|string $id): void
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
        if (isset($this->_fieldLayout)) {
            return $this->_fieldLayout;
        }

        try {
            $id = $this->getFieldLayoutId();
        } catch (InvalidConfigException) {
            $id = null;
        }

        if ($id) {
            $fieldLayout = Craft::$app->getFields()->getLayoutById($id, true);
            if (!$fieldLayout) {
                throw new InvalidConfigException('Invalid field layout ID: ' . $id);
            }
        } else {
            $fieldLayout = new FieldLayout([
                'type' => $this->elementType,
            ]);
        }

        if ($this->owner instanceof EntryType) {
            // Set the provider to the original entry type, so it uses the original provider handle
            // (see https://github.com/craftcms/cms/pull/17213)
            // todo: FieldLayoutProviderInterface could define a getProvider() method
            $fieldLayout->provider = $this->owner->original ?? $this->owner;
        } elseif ($this->owner instanceof FieldLayoutProviderInterface) {
            $fieldLayout->provider = $this->owner;
        }

        return $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Sets the owner's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Returns the custom fields associated with the owner's field layout.
     *
     * @return FieldInterface[]
     * @since 4.0.0
     */
    public function getCustomFields(): array
    {
        /** @var FieldLayout|null $fieldLayout */
        $fieldLayout = $this->owner->getFieldLayout();
        return $fieldLayout ? $fieldLayout->getCustomFields() : [];
    }
}
