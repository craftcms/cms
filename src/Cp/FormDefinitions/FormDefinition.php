<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions;

use CraftCms\Cms\Cp\FormDefinitions\Data\FormDefinitionData;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use InvalidArgumentException;
use JsonSerializable;

readonly class FormDefinition implements JsonSerializable
{
    /** @param list<FormElement> $elements */
    private function __construct(
        private array $elements,
    ) {}

    /** @param list<FormElement> $elements */
    public static function make(array $elements): self
    {
        return new self($elements);
    }

    public function toData(): FormDefinitionData
    {
        $data = new FormDefinitionData(array_map(
            fn (FormElement $element): FormElementData => $element->toData(),
            $this->elements,
        ));

        $names = [];

        foreach ($data->elements as $index => $element) {
            $this->validateElement($element, "elements[{$index}]", $names, root: true);
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
     * @param  bool  $root  Whether the element is at the definition root.
     */
    private function validateElement(
        FormElementData $element,
        string $path,
        array &$names,
        bool $root = false,
    ): void {
        $this->validateType($element, $path);

        if ($element->width !== null && ($element->width < 1 || $element->width > 100)) {
            $this->fail($element, $path, 'width must be between 1 and 100.');
        }

        if ($root && $element->type !== 'craft:field') {
            $this->fail($element, $path, 'an input must be wrapped in a Field Container.');
        }

        $this->validateSerializable($element, $path, 'props', $element->props);
        $this->validateSerializable($element, $path, 'attributes', $element->attributes);

        if ($element->type === 'craft:field') {
            if (count($element->children ?? []) === 1) {
                $this->validateType($element->children[0], "{$path}.children[0]");
            }

            if (
                $element->name !== null
                || count($element->children ?? []) !== 1
                || $element->children[0]->type !== 'craft:text-input'
                || $element->children[0]->name === null
            ) {
                $this->fail($element, $path, 'a Field Container must contain exactly one input.');
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

        foreach ($element->children ?? [] as $index => $child) {
            $this->validateElement($child, "{$path}.children[{$index}]", $names);
        }
    }

    private function validateType(FormElementData $element, string $path): void
    {
        if (! in_array($element->type, ['craft:field', 'craft:text-input'], true)) {
            $this->fail($element, $path, 'unknown Form Element Type.');
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
