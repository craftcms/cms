<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;

abstract class FormElement
{
    protected ?int $width = null;

    /** @var array<string, mixed> */
    protected array $elementAttributes = [];

    protected function __construct(
        protected readonly ?string $name = null,
    ) {}

    abstract public function type(): string;

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    public function attributes(array $attributes): static
    {
        $this->elementAttributes = [...$this->elementAttributes, ...$attributes];

        return $this;
    }

    public function toData(): FormElementData
    {
        $props = $this->props();
        $children = $this->children();

        return new FormElementData(
            type: $this->type(),
            name: $this->name,
            width: $this->width,
            props: $props === [] ? null : $props,
            attributes: $this->elementAttributes === [] ? null : $this->elementAttributes,
            children: $children === []
                ? null
                : array_map(
                    fn (FormElement $element): FormElementData => $element->toData(),
                    $children,
                ),
        );
    }

    /** @return array<string, mixed> */
    protected function props(): array
    {
        return [];
    }

    /** @return list<FormElement> */
    protected function children(): array
    {
        return [];
    }
}
