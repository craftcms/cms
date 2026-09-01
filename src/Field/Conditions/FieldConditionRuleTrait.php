<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use Illuminate\Contracts\Database\Query\Builder;
use RuntimeException;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * FieldConditionRuleTrait implements the common methods and properties for custom fields’ query condition rule classes.
 *
 * @property ElementConditionInterface $condition
 *
 * @method ElementConditionInterface getCondition()
 */
trait FieldConditionRuleTrait
{
    /**
     * @var string The UUID of the custom field associated with this rule
     */
    private string $_fieldUid;

    /**
     * @var string The UUID of the custom field associated with this rule
     */
    public string $fieldUid {
        set {
            $this->setFieldUid($value);
        }
    }

    /**
     * @var string|null The UUID of the custom field layout element associated with this rule
     */
    private ?string $_layoutElementUid = null;

    /**
     * @var FieldInterface[] The custom field instances associated with this rule
     */
    private array $_fieldInstances;

    public function getGroupLabel(): ?string
    {
        return t('Fields');
    }

    public function getFieldUid(): string
    {
        return $this->_fieldUid;
    }

    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
    }

    public function setLayoutElementUid(?string $uid): void
    {
        $this->_layoutElementUid = $uid;
    }

    /**
     * Returns the custom field instances associated with this rule, if known.
     *
     * @return FieldInterface[]
     *
     * @throws RuntimeException if [[fieldUid]] or [[layoutElementUid]] are invalid
     */
    protected function fieldInstances(): array
    {
        if (isset($this->_fieldInstances)) {
            return $this->_fieldInstances;
        }

        if (! isset($this->_fieldUid)) {
            throw new RuntimeException('No field UUID set on the field condition rule yet.');
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
                if ($field->uid === $this->_fieldUid || $field->layoutElement->oldFieldUid === $this->_fieldUid) {
                    // skip if it doesn't have a label
                    $label = $field->layoutElement->label();
                    if ($label === null) {
                        continue;
                    }

                    // make sure this is the expected condition rule class for the field
                    if (! $this->isExpectedType($field)) {
                        continue;
                    }

                    // is this the selected field instance?
                    // (if we aren't looking for a specific instance, include it if the handle isn't overridden)
                    if (
                        (isset($this->_layoutElementUid) && $field->layoutElement->uid === $this->_layoutElementUid) ||
                        (! isset($this->_layoutElementUid) && ! isset($field->layoutElement->handle))
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
            if (! isset($this->_layoutElementUid)) {
                throw new RuntimeException("Field $this->_fieldUid is not included in the available field layouts.");
            }

            if (empty($potentialInstances)) {
                throw new RuntimeException("Invalid field layout element UUID: $this->_layoutElementUid");
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

        return $this->_fieldInstances;
    }

    private function isExpectedType(FieldInterface $field): bool
    {
        $expectedType = $field->getElementConditionRuleType();

        if ($expectedType === null) {
            return false;
        }

        if (is_array($expectedType)) {
            $expectedType = $expectedType['class'];
        }

        return is_a($this, $expectedType);
    }

    /**
     * Returns the first custom field instance associated with this rule.
     *
     * @throws RuntimeException if [[fieldUid]] or [[layoutElementUid]] are invalid
     */
    protected function field(): FieldInterface
    {
        return $this->fieldInstances()[0];
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), array_filter([
            'fieldUid' => $this->_fieldUid,
            'layoutElementUid' => $this->_layoutElementUid,
        ]));
    }

    public function getLabel(): string
    {
        $instances = $this->fieldInstances();
        if (empty($instances)) {
            throw new RuntimeException('No field instances for this condition rule.');
        }

        return $instances[0]->layoutElement->label();
    }

    public function getLabelHint(): ?string
    {
        return $this->field()->handle;
    }

    public function showLabelHint(): bool
    {
        return currentUser()?->getPreference('showFieldHandles') ?? false;
    }

    public function getExclusiveQueryParams(): array
    {
        try {
            $instances = $this->fieldInstances();
        } catch (RuntimeException) {
            return [];
        }

        $params = [];
        foreach ($instances as $field) {
            $params[] = $field->handle;
        }

        return array_values(array_unique($params));
    }

    public function modifyQuery(Builder $query): void
    {
        $value = $this->elementQueryParam();

        if ($value === null) {
            return;
        }

        $instances = $this->fieldInstances();
        $firstInstance = $instances[0];

        if (! method_exists($firstInstance, 'modifyQuery')) {
            return;
        }

        $firstInstance::modifyQuery($query, $instances, $value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        try {
            $fieldInstances = $this->fieldInstances();
        } catch (RuntimeException) {
            // The field doesn't exist
            return true;
        }

        // index the field instance UUIDs
        $instanceUids = array_flip(
            array_map(fn (FieldInterface $field) => $field->layoutElement->uid, $fieldInstances),
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

    abstract protected function elementQueryParam(): mixed;

    abstract protected function matchFieldValue(mixed $value): bool;

    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'fieldUid' => ['nullable', 'uuid'],
            'layoutElementUid' => ['nullable', 'uuid'],
        ]);
    }
}
