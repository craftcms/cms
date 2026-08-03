<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\Form;
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
        $name = $this->resolvedText($this->name);

        if ($name === null) {
            $this->invalidOutputOption('name', 'Form');
        }

        $attributes = $this->withoutAttributes($this->formElementAttributes, [
            ...Form::HostOwnedRendererAttributes,
            'disabled',
            'slot',
            'type',
            'value',
        ]);

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

    protected function portableNumber(string $option, mixed $value): int|float|null
    {
        $value = $this->evaluate($value);

        if ($value !== null && ! is_int($value) && ! is_float($value)) {
            $this->invalidOutputOption($option, 'Form');
        }

        return $value;
    }

    private function rejectNativeTypeOverride(): void
    {
        foreach (array_keys($this->inputAttributes) as $attribute) {
            if (strtolower((string) $attribute) === 'type') {
                $this->invalidOutputOption("inputAttributes.{$attribute}", 'HTML');
            }
        }
    }
}
