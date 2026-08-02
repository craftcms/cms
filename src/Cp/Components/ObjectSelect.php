<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Support\Json;
use Override;

class ObjectSelect extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    /** @var list<mixed>|Closure */
    protected array|Closure $value = [];

    /** @var list<array{key: string, label: string, value: mixed}>|Closure */
    protected array|Closure $options = [];

    protected string|Closure|null $identityKey = null;

    protected bool|Closure $readOnly = false;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:object-select-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-object-select';
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    /** @param list<mixed>|Closure $value */
    public function value(array|Closure $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    /** @param list<array{key: string, label: string, value: mixed}>|Closure $options */
    public function options(array|Closure $options): static
    {
        $this->trackConfiguration('options');
        $this->options = $options;

        return $this;
    }

    public function identityKey(string|Closure|null $identityKey): static
    {
        $this->trackConfiguration('identityKey');
        $this->identityKey = $identityKey;

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
        $this->rejectConfiguredOptions(['value', 'readOnly', 'slot'], 'Form');

        $name = $this->portableText('name', $this->name);
        $identityKey = $this->portableText('identityKey', $this->identityKey);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form');
        }

        if ($identityKey === null) {
            $this->unsupportedOutputOption('identityKey', 'Form');
        }

        $this->validateAttributes();

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: [
                'options' => $this->resolvedOptions('Form'),
                'identityKey' => $identityKey,
            ],
            attributes: $this->formElementAttributes === [] ? null : $this->formElementAttributes,
        );
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $value = $this->evaluate($this->value);

        if (! is_array($value) || ! array_is_list($value)) {
            $this->unsupportedOutputOption('value', 'HTML');
        }

        foreach ($value as $index => $item) {
            $this->validateJsonValue($item, "value[{$index}]", 'HTML');
        }

        return [
            'name' => $this->resolvedText('name', $this->name, 'HTML'),
            'value' => Json::encode($value, JSON_THROW_ON_ERROR),
            'options' => Json::encode($this->resolvedOptions('HTML'), JSON_THROW_ON_ERROR),
            'identity-key' => $this->resolvedText('identityKey', $this->identityKey, 'HTML'),
            'readonly' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ];
    }

    /** @return list<array{key: string, label: string, value: mixed}> */
    private function resolvedOptions(string $output): array
    {
        $options = $this->evaluate($this->options);

        if (! is_array($options) || ! array_is_list($options)) {
            $this->unsupportedOutputOption('options', $output);
        }

        foreach ($options as $index => $option) {
            if (! is_array($option)) {
                $this->unsupportedOutputOption("options[{$index}]", $output);
            }

            foreach (array_keys($option) as $property) {
                if (! in_array($property, ['key', 'label', 'value'], true)) {
                    $this->unsupportedOutputOption("options[{$index}].{$property}", $output);
                }
            }

            if (
                ! isset($option['key'], $option['label'])
                || ! is_string($option['key'])
                || ! is_string($option['label'])
                || ! array_key_exists('value', $option)
            ) {
                $this->unsupportedOutputOption("options[{$index}]", $output);
            }

            $this->validateJsonValue($option['value'], "options[{$index}].value", $output);
        }

        return $options;
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
                'aria-required',
                'id',
                'identity-key',
                'name',
                'options',
                'readonly',
                'required',
                'slot',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form');
            }
        }

        $aria = $this->formElementAttributes['aria'] ?? null;

        if (! is_array($aria)) {
            return;
        }

        foreach (['describedby', 'labelledby', 'required'] as $attribute) {
            if (array_key_exists($attribute, $aria)) {
                $this->unsupportedOutputOption("attributes.aria-{$attribute}", 'Form');
            }
        }
    }
}
