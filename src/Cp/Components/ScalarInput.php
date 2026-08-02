<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use Override;

abstract class ScalarInput extends Input implements FormElement
{
    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    #[Override]
    public function toHtml(): string
    {
        $this->rejectConfiguredOptions(['type'], 'HTML');
        $this->rejectNativeTypeOverride();

        return parent::toHtml();
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
        $this->rejectConfiguredOptions([
            'type',
            'value',
            'maxlength',
            'inputSize',
            'width',
            'small',
            'center',
            'monospace',
            'hiddenInput',
            'autofocus',
            'autocomplete',
            'autocorrect',
            'autocapitalize',
            'readOnly',
            'title',
            'inputmode',
            'orientation',
            'role',
            'expanded',
            'suffix',
            'descriptionId',
            'showCharsLeft',
            'labelledBy',
            'describedBy',
            'inputAttributes',
            'disabled',
            'id',
            'size',
            'slot',
            ...$this->unsupportedPortableOptions(),
        ], 'Form');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form');
        }

        $attributes = $this->formElementAttributes;

        foreach (array_keys($attributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'aria-required',
                'disabled',
                'id',
                'name',
                'readonly',
                'required',
                'slot',
                'type',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form');
            }
        }

        $props = array_filter(
            $this->formElementProps(),
            fn (mixed $value): bool => $value !== null,
        );

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props === [] ? null : $props,
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    /** @return array<string, mixed> */
    protected function formElementProps(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function unsupportedPortableOptions(): array
    {
        return ['placeholder', 'min', 'max', 'step'];
    }

    protected function portableNumber(string $option, mixed $value): int|float|null
    {
        $value = $this->evaluate($value);

        if ($value !== null && ! is_int($value) && ! is_float($value)) {
            $this->unsupportedOutputOption($option, 'Form');
        }

        return $value;
    }

    private function rejectNativeTypeOverride(): void
    {
        foreach (array_keys($this->inputAttributes) as $attribute) {
            if (strtolower((string) $attribute) === 'type') {
                $this->unsupportedOutputOption("inputAttributes.{$attribute}", 'HTML');
            }
        }
    }
}
