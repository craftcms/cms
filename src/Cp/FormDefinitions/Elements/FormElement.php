<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;

abstract class FormElement
{
    protected ?int $width = null;

    /** @var array<string, mixed> */
    protected array $elementAttributes = [];

    protected ?Condition $visibleWhen = null;

    protected function __construct(
        protected readonly ?string $name = null,
    ) {}

    abstract public static function type(): string;

    public static function isContainer(): bool
    {
        return false;
    }

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

    public function visibleWhen(Condition $condition): static
    {
        $this->visibleWhen = $condition;

        return $this;
    }

    public function toData(): FormElementData
    {
        $props = $this->props();
        $children = $this->children();

        return new FormElementData(
            type: static::type(),
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
            visibleWhen: $this->visibleWhen?->toData(),
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
