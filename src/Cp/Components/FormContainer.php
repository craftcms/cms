<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use InvalidArgumentException;
use Override;

abstract class FormContainer extends ViewComponent implements FormElement
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
        $this->trackConfiguration('visibleWhen');
        $this->visibilityCondition = $condition;

        return $this;
    }

    #[Override]
    public function toHtml(): string
    {
        $this->rejectConfiguredOptions(['visibleWhen'], 'HTML');

        return parent::toHtml();
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions(['slot'], 'Form Definition');

        foreach (array_keys($this->attributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), $this->hostOwnedFormElementAttributes(), true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }

        $key = $this->resolvedElementKey('Form Definition');
        $width = $this->resolvedColumnWidth('Form Definition');
        $condition = $this->resolvedVisibilityCondition('Form Definition');

        $props = $this->formElementProps();
        $children = $this->formElementChildren();

        return new FormElementData(
            type: static::formElementType(),
            key: $key,
            width: $width,
            props: $props === [] ? null : $props,
            attributes: $this->attributes === [] ? null : $this->attributes,
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

        foreach ($this->resolvedChildren('Form Definition') as $index => $child) {
            if ($child instanceof Tab) {
                $this->invalidChild($index, $child, 'a non-Tab form element', 'Form Definition');
            }

            if ($child instanceof FormElement) {
                $children[] = app(FormElementTypes::class)->project($child);

                continue;
            }

            $this->invalidChild(
                $index,
                $child,
                FormElement::class,
                'Form Definition',
            );
        }

        return $children;
    }

    /** @return iterable<array-key, mixed> */
    protected function resolvedChildren(string $output): iterable
    {
        $children = $this->evaluate($this->children);

        if (! is_iterable($children)) {
            $this->unsupportedOutputOption('children', $output);
        }

        return $children;
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

    protected function resolvedElementKey(string $output): ?string
    {
        $key = $this->evaluate($this->elementKey);

        if ($key !== null && ! is_string($key)) {
            $this->unsupportedOutputOption('key', $output);
        }

        return $key;
    }

    protected function resolvedColumnWidth(string $output): ?int
    {
        $width = $this->evaluate($this->columnWidth);

        if ($width !== null && ! is_int($width)) {
            $this->unsupportedOutputOption('columnWidth', $output);
        }

        return $width;
    }

    protected function resolvedVisibilityCondition(string $output): ?Condition
    {
        $condition = $this->evaluate($this->visibilityCondition);

        if ($condition !== null && ! $condition instanceof Condition) {
            $this->unsupportedOutputOption('visibleWhen', $output);
        }

        return $condition;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $key = $this->resolvedElementKey('HTML');
        $width = $this->resolvedColumnWidth('HTML');

        return [
            'data-form-element-key' => $key,
            'style' => $width !== null ? "width: {$width}%;" : null,
        ];
    }
}
