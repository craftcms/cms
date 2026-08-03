<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Forms\Condition;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Contracts\PositionableFormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use InvalidArgumentException;
use Override;

abstract class FormContainer extends ViewComponent implements PositionableFormElement
{
    protected iterable|Closure $children = [];

    protected string|Closure|null $elementKey = null;

    protected int|Closure|null $columnWidth = null;

    protected Condition|Closure|null $visibilityCondition = null;

    public static function isFormElementContainer(): bool
    {
        return true;
    }

    public function children(iterable|Closure $children): static
    {
        if (! $children instanceof Closure && ! is_array($children)) {
            $children = iterator_to_array($children, preserve_keys: false);
        }

        $this->children = $children;
        $this->slots[static::DEFAULT_SLOT] = $children;

        return $this;
    }

    public function key(string|Closure|null $key): static
    {
        $this->elementKey = $key;

        return $this;
    }

    public function columnWidth(int|Closure|null $width): static
    {
        $this->columnWidth = $width;

        return $this;
    }

    public function visibleWhen(Condition|Closure|null $condition): static
    {
        $this->visibilityCondition = $condition;

        return $this;
    }

    public function toFormElementData(): FormElementData
    {
        $key = $this->resolvedElementKey();
        $width = $this->resolvedColumnWidth();
        $condition = $this->resolvedVisibilityCondition();

        $props = $this->formElementProps();
        $attributes = $this->withoutAttributes($this->attributes, [
            ...Form::HostOwnedRendererAttributes,
            ...$this->hostOwnedFormElementAttributes(),
        ]);
        $children = $this->formElementChildren();

        return new FormElementData(
            type: static::formElementType(),
            key: $key,
            width: $width,
            props: $props === [] ? null : $props,
            attributes: $attributes === [] ? null : $attributes,
            children: $children === [] ? null : $children,
            visibleWhen: $condition?->toData(),
        );
    }

    /** @return array<string, mixed> */
    protected function formElementProps(): array
    {
        return [];
    }

    /** @return list<string> */
    protected function hostOwnedFormElementAttributes(): array
    {
        return ['slot'];
    }

    /** @return list<FormElementData> */
    protected function formElementChildren(): array
    {
        $children = [];

        foreach ($this->resolvedChildren() as $index => $child) {
            if ($child instanceof Tab) {
                $this->invalidChild($index, $child, 'a non-Tab form element', 'Form');
            }

            if ($child instanceof FormElement) {
                $children[] = app(FormElementTypes::class)->project($child);

                continue;
            }

            $this->invalidChild(
                $index,
                $child,
                FormElement::class,
                'Form',
            );
        }

        return $children;
    }

    /** @return iterable<array-key, mixed> */
    protected function resolvedChildren(): iterable
    {
        return $this->evaluate($this->children);
    }

    protected function invalidChild(int|string $index, mixed $child, string $expected, string $output): never
    {
        throw new InvalidArgumentException(sprintf(
            '%s child at index %s (%s) must be %s for %s output.',
            static::class,
            $index,
            get_debug_type($child),
            $expected,
            $output,
        ));
    }

    protected function resolvedElementKey(): ?string
    {
        return $this->evaluate($this->elementKey);
    }

    protected function resolvedColumnWidth(): ?int
    {
        return $this->evaluate($this->columnWidth);
    }

    protected function resolvedVisibilityCondition(): ?Condition
    {
        return $this->evaluate($this->visibilityCondition);
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $key = $this->resolvedElementKey();
        $width = $this->resolvedColumnWidth();

        return [
            'data-form-element-key' => $key,
            'style' => $width !== null ? "width: {$width}%;" : null,
        ];
    }
}
