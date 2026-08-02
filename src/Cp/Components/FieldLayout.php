<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Json;
use Override;

class FieldLayout extends ViewComponent implements ProjectableFormElement
{
    protected string|Closure|null $name = null;

    /** @var array<string, mixed>|Closure */
    protected array|Closure $value = [];

    /**
     * @var list<array{
     *     key: string,
     *     label: string,
     *     value: array<string, mixed>,
     *     multiple: bool,
     * }>|Closure
     */
    protected array|Closure $availableElements = [];

    protected bool|Closure $withGeneratedFields = false;

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:field-layout-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-field-layout';
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param array<string, mixed>|Closure $value */
    public function value(array|Closure $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    /**
     * @param list<array{
     *     key: string,
     *     label: string,
     *     value: array<string, mixed>,
     *     multiple: bool,
     * }>|Closure $availableElements
     */
    public function availableElements(array|Closure $availableElements): static
    {
        $this->trackConfiguration('availableElements');
        $this->availableElements = $availableElements;

        return $this;
    }

    public function withGeneratedFields(bool|Closure $withGeneratedFields = true): static
    {
        $this->trackConfiguration('withGeneratedFields');
        $this->withGeneratedFields = $withGeneratedFields;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->trackConfiguration('readOnly');
        $this->readOnly = $readOnly;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    #[Override]
    public function attributes(array $attributes): static
    {
        $this->formElementAttributes = [...$this->formElementAttributes, ...$attributes];

        return parent::attributes($attributes);
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions(['value', 'readOnly', 'slot'], 'Form Definition');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        $this->validateAttributes();

        $props = [
            'availableElements' => $this->resolvedAvailableElements('Form Definition'),
            'withGeneratedFields' => $this->resolvedBool('withGeneratedFields', $this->withGeneratedFields, 'Form Definition'),
        ];

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props,
            attributes: $this->formElementAttributes === [] ? null : $this->formElementAttributes,
        );
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $value = $this->evaluate($this->value);

        if (! is_array($value) || array_is_list($value) && $value !== []) {
            $this->unsupportedOutputOption('value', 'HTML');
        }

        $this->validateJsonValue($value, 'value', 'HTML');

        return [
            'name' => $this->resolvedText('name', $this->name, 'HTML'),
            'value' => Json::encode($value, JSON_THROW_ON_ERROR),
            'available-elements' => Json::encode($this->resolvedAvailableElements('HTML'), JSON_THROW_ON_ERROR),
            'with-generated-fields' => $this->resolvedBool('withGeneratedFields', $this->withGeneratedFields, 'HTML'),
            'readonly' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ];
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     value: array<string, mixed>,
     *     multiple: bool,
     * }>
     */
    private function resolvedAvailableElements(string $output): array
    {
        $availableElements = $this->evaluate($this->availableElements);

        if (! is_array($availableElements) || ! array_is_list($availableElements)) {
            $this->unsupportedOutputOption('availableElements', $output);
        }

        foreach ($availableElements as $index => $element) {
            if (
                ! is_array($element)
                || array_diff(array_keys($element), ['key', 'label', 'value', 'multiple']) !== []
                || ! isset($element['key'], $element['label'], $element['value'], $element['multiple'])
                || ! is_string($element['key'])
                || ! is_string($element['label'])
                || ! is_array($element['value'])
                || array_is_list($element['value']) && $element['value'] !== []
                || ! is_bool($element['multiple'])
            ) {
                $this->unsupportedOutputOption("availableElements[{$index}]", $output);
            }

            $this->validateJsonValue($element['value'], "availableElements[{$index}].value", $output);
        }

        return $availableElements;
    }

    private function validateJsonValue(mixed $value, string $option, string $output): void
    {
        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                $this->unsupportedOutputOption($option, $output);
            }

            return;
        }

        if (! is_array($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        foreach ($value as $key => $item) {
            $this->validateJsonValue($item, "{$option}[{$key}]", $output);
        }
    }

    private function resolvedText(string $option, string|Closure|null $value, string $output): ?string
    {
        $value = $this->evaluate($value);

        if ($value !== null && ! is_string($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function resolvedBool(string $option, bool|Closure $value, string $output): bool
    {
        $value = $this->evaluate($value);

        if (! is_bool($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }

    private function validateAttributes(): void
    {
        foreach (array_keys($this->formElementAttributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'available-elements',
                'id',
                'name',
                'readonly',
                'slot',
                'value',
                'with-generated-fields',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }

        $aria = $this->formElementAttributes['aria'] ?? null;

        if (! is_array($aria)) {
            return;
        }

        foreach (['describedby', 'labelledby'] as $attribute) {
            if (array_key_exists($attribute, $aria)) {
                $this->unsupportedOutputOption("attributes.aria-{$attribute}", 'Form Definition');
            }
        }
    }
}
