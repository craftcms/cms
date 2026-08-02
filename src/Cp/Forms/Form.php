<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms;

use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\Data\FormPayload;
use CraftCms\Cms\Cp\Forms\Data\VisibilityConditionData;
use InvalidArgumentException;
use JsonSerializable;

readonly class Form implements JsonSerializable
{
    public const array HostOwnedRendererProps = [
        'aria-describedby',
        'aria-labelledby',
        'aria-required',
        'id',
        'model-value',
        'modelValue',
        'name',
        'readonly',
        'required',
    ];

    public const array HostOwnedRendererAttributes = [
        'aria-describedby',
        'aria-labelledby',
        'aria-required',
        'id',
        'name',
        'readonly',
        'required',
    ];

    /** @param list<FormElement> $elements */
    private function __construct(
        private array $elements,
    ) {}

    /** @param list<FormElement> $elements */
    public static function make(array $elements): self
    {
        return new self($elements);
    }

    public function toData(): FormPayload
    {
        $types = app(FormElementTypes::class);
        $data = new FormPayload(array_map(
            fn (FormElement $element): FormElementData => $types
                ->project($element)
                ->withPluginOwnership($types->ownership(...)),
            $this->elements,
        ));

        $names = [];
        $keys = [];

        foreach ($data->elements as $index => $element) {
            $this->validateElement($element, "elements[{$index}]", $names, $keys, root: true);
        }

        foreach ($data->elements as $index => $element) {
            $this->validateElementVisibility($element, "elements[{$index}]", $names);
        }

        return $data;
    }

    /** @return array{elements: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return $this->toData()->jsonSerialize();
    }

    /** @return array{elements: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  FormElementData  $element  Element being validated.
     * @param  string  $path  Element location in the tree.
     * @param  array<string, string>  $names  Previously encountered Input Names.
     * @param  array<string, string>  $keys  Previously encountered sibling keys.
     * @param  ?string  $parentType  Parent Form Element Type.
     * @param  bool  $root  Whether the element is at the form root.
     */
    private function validateElement(
        FormElementData $element,
        string $path,
        array &$names,
        array &$keys,
        ?string $parentType = null,
        bool $root = false,
    ): void {
        $this->validateType($element, $path);

        if ($element->key !== null) {
            if ($element->key === '') {
                $this->fail($element, $path, 'key cannot be empty.');
            }

            if (isset($keys[$element->key])) {
                $this->fail($element, $path, "duplicate sibling key \"{$element->key}\".");
            }

            $keys[$element->key] = $path;
        }

        if ($element->width !== null && ($element->width < 1 || $element->width > 100)) {
            $this->fail($element, $path, 'width must be between 1 and 100.');
        }

        $types = app(FormElementTypes::class);

        if ($parentType === 'craft:tabs' && $element->type !== 'craft:tab') {
            $this->fail($element, $path, 'only Tab Form Elements may be direct children of Tabs.');
        }

        if ($element->type === 'craft:tab' && $parentType !== 'craft:tabs') {
            $this->fail($element, $path, 'a Tab must be a direct child of Tabs.');
        }

        if ($root && $element->type !== 'craft:field' && ! $types->isContainer($element->type)) {
            $this->fail($element, $path, 'an input must be wrapped in a Field Container.');
        }

        $this->validateRendererPropOwnership($element, $path);
        $this->validateSerializable($element, $path, 'props', $element->props);
        $this->validateSerializable($element, $path, 'attributes', $element->attributes);

        if ($element->type === 'craft:field') {
            if (count($element->children ?? []) === 1) {
                $this->validateType($element->children[0], "{$path}.children[0]");
            }

            if (
                $element->name !== null
                || count($element->children ?? []) !== 1
                || $element->children[0]->name === null
            ) {
                $this->fail($element, $path, 'a Field Container must contain exactly one input.');
            }
        } elseif ($element->type === 'craft:tabs' && ($element->children ?? []) === []) {
            $this->fail($element, $path, 'Tabs must contain at least one Tab.');
        } elseif ($element->type === 'craft:tab') {
            if ($element->key === null) {
                $this->fail($element, $path, 'a Tab must define a stable key.');
            }

            if (! is_string($element->props['label'] ?? null) || $element->props['label'] === '') {
                $this->fail($element, $path, 'a Tab must define a resolved label.');
            }
        } elseif ($types->isContainer($element->type)) {
            if ($element->name !== null) {
                $this->fail($element, $path, 'a Form Container cannot define an Input Name.');
            }
        } elseif ($element->children !== null) {
            $this->fail($element, $path, 'this type cannot contain children.');
        }

        if ($element->name !== null) {
            if ($element->name === '') {
                $this->fail($element, $path, 'Input Name cannot be empty.');
            }

            if (isset($names[$element->name])) {
                $this->fail($element, $path, "duplicate Input Name \"{$element->name}\".");
            }

            $names[$element->name] = $path;
        }

        $childKeys = [];

        foreach ($element->children ?? [] as $index => $child) {
            $this->validateElement(
                $child,
                "{$path}.children[{$index}]",
                $names,
                $childKeys,
                parentType: $element->type,
            );
        }
    }

    private function validateType(FormElementData $element, string $path): void
    {
        if (! app(FormElementTypes::class)->isRegistered($element->type)) {
            $this->fail($element, $path, 'unknown or unregistered Form Element Type.');
        }
    }

    /**
     * @param  FormElementData  $element  Element whose visibility is being validated.
     * @param  string  $path  Element location in the tree.
     * @param  array<string, string>  $names  Resolved Input Names.
     */
    private function validateElementVisibility(FormElementData $element, string $path, array $names): void
    {
        if ($element->visibleWhen !== null) {
            $this->validateVisibilityCondition($element, $element->visibleWhen, "{$path}.visibleWhen", $names);
        }

        foreach ($element->children ?? [] as $index => $child) {
            $this->validateElementVisibility($child, "{$path}.children[{$index}]", $names);
        }
    }

    /**
     * @param  FormElementData  $element  Element owning the condition.
     * @param  VisibilityConditionData  $condition  Condition being validated.
     * @param  string  $path  Condition location in the tree.
     * @param  array<string, string>  $names  Resolved Input Names.
     */
    private function validateVisibilityCondition(
        FormElementData $element,
        VisibilityConditionData $condition,
        string $path,
        array $names,
    ): void {
        $data = $condition->condition;

        foreach (['all', 'any'] as $group) {
            if (! array_key_exists($group, $data)) {
                continue;
            }

            if (count($data) !== 1 || ! is_array($data[$group]) || ! array_is_list($data[$group])) {
                $this->fail($element, $path, "{$group} must be the condition's only property and contain a list.");
            }

            if ($data[$group] === []) {
                $this->fail($element, $path, "{$group} groups cannot be empty.");
            }

            foreach ($data[$group] as $index => $child) {
                if (! $child instanceof VisibilityConditionData) {
                    $this->fail($element, "{$path}.{$group}[{$index}]", 'group members must be Visibility Conditions.');
                }

                $this->validateVisibilityCondition($element, $child, "{$path}.{$group}[{$index}]", $names);
            }

            return;
        }

        $name = $data['name'] ?? null;
        $operator = $data['operator'] ?? null;

        if (! is_string($name) || $name === '') {
            $this->fail($element, $path, 'comparison name must be a non-empty Input Name.');
        }

        if (! isset($names[$name])) {
            $this->fail($element, $path, "unresolved Input Name \"{$name}\".");
        }

        $operators = [
            'equals',
            'notEquals',
            'lessThan',
            'lessThanOrEqual',
            'greaterThan',
            'greaterThanOrEqual',
            'beginsWith',
            'endsWith',
            'contains',
            'in',
            'notIn',
            'empty',
            'notEmpty',
        ];

        if (! is_string($operator) || ! in_array($operator, $operators, true)) {
            $displayOperator = is_scalar($operator) ? (string) $operator : get_debug_type($operator);
            $this->fail($element, $path, "unsupported operator \"{$displayOperator}\".");
        }

        $hasValue = array_key_exists('value', $data);
        $expectedKeys = in_array($operator, ['empty', 'notEmpty'], true)
            ? ['name', 'operator']
            : ['name', 'operator', 'value'];

        if (array_diff(array_keys($data), $expectedKeys) !== [] || array_diff($expectedKeys, array_keys($data)) !== []) {
            if (in_array($operator, ['empty', 'notEmpty'], true) && $hasValue) {
                $this->fail($element, $path, "{$operator} does not accept a value.");
            }

            $this->fail($element, $path, 'comparison has malformed operands.');
        }

        if (! $hasValue) {
            return;
        }

        $value = $data['value'];

        if (is_object($value) && is_callable($value)) {
            $this->fail($element, $path, 'value cannot be executable.');
        }

        if (in_array($operator, ['lessThan', 'lessThanOrEqual', 'greaterThan', 'greaterThanOrEqual'], true)) {
            if (! is_int($value) && (! is_float($value) || ! is_finite($value))) {
                $this->fail($element, $path, "{$operator} requires a numeric value.");
            }

            return;
        }

        if (in_array($operator, ['beginsWith', 'endsWith'], true)) {
            if (! is_string($value)) {
                $this->fail($element, $path, "{$operator} requires a string value.");
            }

            return;
        }

        if (in_array($operator, ['in', 'notIn'], true)) {
            if (! $this->isScalarList($value)) {
                $this->fail($element, $path, "{$operator} requires a list of scalar values.");
            }

            return;
        }

        if ($operator === 'contains') {
            if (! $this->isVisibilityScalar($value)) {
                $this->fail($element, $path, 'contains requires a scalar value.');
            }

            return;
        }

        if (! $this->isVisibilityScalar($value) && ! $this->isScalarList($value)) {
            $this->fail($element, $path, "{$operator} requires a scalar value or a list of scalar values.");
        }
    }

    private function isVisibilityScalar(mixed $value): bool
    {
        return $value === null
            || is_bool($value)
            || is_int($value)
            || is_string($value)
            || (is_float($value) && is_finite($value));
    }

    private function isScalarList(mixed $value): bool
    {
        return is_array($value)
            && array_is_list($value)
            && array_all($value, fn (mixed $item): bool => $this->isVisibilityScalar($item));
    }

    private function validateRendererPropOwnership(FormElementData $element, string $path): void
    {
        foreach (array_keys($element->props ?? []) as $prop) {
            if ($element->type === 'craft:field' && $prop === 'required') {
                continue;
            }

            if (in_array($prop, self::HostOwnedRendererProps, true)) {
                $this->fail($element, $path, "renderer prop \"{$prop}\" is owned by the Form host.");
            }
        }

        foreach ($element->attributes ?? [] as $attribute => $value) {
            $attributes = $attribute === 'aria' && is_array($value)
                ? array_map(fn (string $name): string => "aria-{$name}", array_keys($value))
                : [(string) $attribute];

            foreach ($attributes as $rendererAttribute) {
                $rendererAttribute = strtolower($rendererAttribute);

                if (in_array($rendererAttribute, self::HostOwnedRendererAttributes, true)) {
                    $this->fail($element, $path, "renderer attribute \"{$rendererAttribute}\" is owned by the Form host.");
                }
            }
        }
    }

    /**
     * @param  FormElementData  $element  Element owning the values.
     * @param  string  $path  Element location in the tree.
     * @param  string  $property  Property name used in diagnostics.
     * @param  array<string, mixed>|null  $values  Values to validate.
     */
    private function validateSerializable(
        FormElementData $element,
        string $path,
        string $property,
        ?array $values,
    ): void {
        foreach ($values ?? [] as $key => $value) {
            $this->validateSerializableValue($element, $path, "{$property}.{$key}", $value);
        }
    }

    private function validateSerializableValue(
        FormElementData $element,
        string $path,
        string $property,
        mixed $value,
    ): void {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $this->validateSerializableValue($element, $path, "{$property}.{$key}", $child);
            }

            return;
        }

        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return;
        }

        if (is_float($value) && is_finite($value)) {
            return;
        }

        $this->fail($element, $path, "{$property} is not serializable.");
    }

    private function fail(FormElementData $element, string $path, string $message): never
    {
        throw new InvalidArgumentException(
            "Form Element Type \"{$element->type}\" at {$path}: {$message}",
        );
    }
}
