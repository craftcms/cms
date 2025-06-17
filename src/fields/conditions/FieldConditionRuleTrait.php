<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\conditions\ElementConditionInterface;
use yii\base\InvalidConfigException;
use yii\db\QueryInterface;

/**
 * FieldConditionRuleTrait implements the common methods and properties for custom fields’ query condition rule classes.
 *
 * @property ElementConditionInterface $condition
 * @method ElementConditionInterface getCondition()
 * @property-write string $fieldUid The UUID of the custom field associated with this rule
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait FieldConditionRuleTrait
{
    /**
     * @var string The UUID of the custom field associated with this rule
     */
    private string $_fieldUid;

    /**
     * @var string|null The UUID of the custom field layout element associated with this rule
     */
    private ?string $_layoutElementUid = null;

    /**
     * @var FieldInterface[] The custom field instances associated with this rule
     */
    private array $_fieldInstances;

    /**
     * @inheritdoc
     */
    public function getGroupLabel(): ?string
    {
        return Craft::t('app', 'Fields');
    }

    /**
     * @inheritdoc
     */
    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
    }

    /**
     * @inheritdoc
     */
    public function setLayoutElementUid(?string $uid): void
    {
        $this->_layoutElementUid = $uid;
    }

    /**
     * Returns the custom field instances associated with this rule, if known.
     *
     * @return FieldInterface[]
     * @throws InvalidConfigException if [[fieldUid]] or [[layoutElementUid]] are invalid
     * @since 5.0.0
     */
    protected function fieldInstances(): array
    {
        if (!isset($this->_fieldInstances)) {
            if (!isset($this->_fieldUid)) {
                throw new InvalidConfigException('No field UUID set on the field condition rule yet.');
            }

            // Loop through all the layout's fields, and look for the selected field instance
            // and any other instances with the same label and handle
            $this->_fieldInstances = [];
            /** @var FieldInterface[] $potentialInstances */
            $potentialInstances = [];
            $selectedInstance = null;
            $selectedInstanceLabel = null;

            foreach ($this->getCondition()->getFieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    if ($field->uid === $this->_fieldUid) {
                        // skip if it doesn't have a label
                        $label = $field->layoutElement->label();
                        if ($label === null) {
                            continue;
                        }

                        // is this the selected field instance?
                        // (if we aren't looking for a specific instance, include it if the handle isn't overridden)
                        if (
                            (isset($this->_layoutElementUid) && $field->layoutElement->uid === $this->_layoutElementUid) ||
                            (!isset($this->_layoutElementUid) && !isset($field->layoutElement->handle))
                        ) {
                            $this->_fieldInstances[] = $field;

                            if (isset($this->_layoutElementUid)) {
                                $selectedInstance = $field;
                                $selectedInstanceLabel = $label;
                            }
                        } elseif (isset($this->_layoutElementUid)) {
                            $potentialInstances[] = $field;
                        }
                    }
                }
            }

            if (empty($this->_fieldInstances)) {
                if (!isset($this->_layoutElementUid)) {
                    throw new InvalidConfigException("Field $this->_fieldUid is not included in the available field layouts.");
                }

                if (empty($potentialInstances)) {
                    throw new InvalidConfigException("Invalid field layout element UUID: $this->_layoutElementUid");
                }

                // Just go with the first one
                $this->_fieldInstances[] = $first = array_shift($potentialInstances);
                $selectedInstance = $first;
                $selectedInstanceLabel = $first->layoutElement->label();
            }

            // Add any potential fields to the mix if they have a matching label and handle
            foreach ($potentialInstances as $field) {
                if (
                    $field->handle === $selectedInstance->handle &&
                    $field->layoutElement->label() === $selectedInstanceLabel
                ) {
                    $this->_fieldInstances[] = $field;
                }
            }
        }

        return $this->_fieldInstances;
    }

    /**
     * Returns the first custom field instance associated with this rule.
     *
     * @return FieldInterface
     * @throws InvalidConfigException if [[fieldUid]] or [[layoutElementUid]] are invalid
     */
    protected function field(): FieldInterface
    {
        return $this->fieldInstances()[0];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), array_filter([
            'fieldUid' => $this->_fieldUid,
            'layoutElementUid' => $this->_layoutElementUid,
        ]));
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $instances = $this->fieldInstances();
        if (empty($instances)) {
            throw new InvalidConfigException('No field instances for this condition rule.');
        }
        return $instances[0]->layoutElement->label();
    }

    /**
     * @inheritdoc
     */
    public function getLabelHint(): ?string
    {
        return $this->field()->handle;
    }

    /**
     * @inheritdoc
     */
    public function showLabelHint(): bool
    {
        return Craft::$app->getUser()->getIdentity()?->getPreference('showFieldHandles') ?? false;
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        try {
            $instances = $this->fieldInstances();
        } catch (InvalidConfigException) {
            return [];
        }

        $params = [];
        foreach ($instances as $field) {
            $params[] = $field->handle;
        }
        return array_values(array_unique($params));
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $value = $this->elementQueryParam();
        if ($value !== null) {
            $instances = $this->fieldInstances();
            $firstInstance = $instances[0];
            $params = [];
            $condition = $firstInstance::queryCondition($instances, $value, $params);

            if ($condition === false) {
                /** @phpstan-ignore-next-line */
                $query->andWhere('0=1');
            } elseif ($condition !== null) {
                /** @phpstan-ignore-next-line */
                $query->andWhere($condition, $params);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        try {
            $fieldInstances = $this->fieldInstances();
        } catch (InvalidConfigException) {
            // The field doesn't exist
            return true;
        }

        // index the field instance UUIDs
        $instanceUids = array_flip(
            array_map(fn(FieldInterface $field) => $field->layoutElement->uid, $fieldInstances),
        );

        foreach ($element->getFieldLayout()->getCustomFields() as $field) {
            if (isset($instanceUids[$field->layoutElement->uid])) {
                $value = $element->getFieldValue($field->handle);
                if ($this->matchFieldValue($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    abstract protected function elementQueryParam(): mixed;

    /**
     * @return mixed
     */
    abstract protected function matchFieldValue($value): bool;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['fieldUid', 'layoutElementUid'], 'safe'],
        ]);
    }
}
