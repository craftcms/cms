<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner as Designer;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\InputNamespace;
use InvalidArgumentException;

class FieldLayoutDesigner extends Control
{
    private ?string $elementType = null;

    private bool $customizableTabs = true;

    private bool $withGeneratedFields = false;

    private bool $withCardViewDesigner = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::designerHtml(
            is_array($value) ? $value : [],
            $control->props['elementType'],
            $attributes['name'],
            $attributes['name'] === null,
            (bool) $control->props['customizableTabs'],
            (bool) $control->props['withGeneratedFields'],
            (bool) $control->props['withCardViewDesigner'],
        );
    }

    /** @param array<string, mixed> $value */
    public static function designerHtml(
        array $value,
        string $elementType,
        ?string $name,
        bool $disabled,
        bool $customizableTabs,
        bool $withGeneratedFields = false,
        bool $withCardViewDesigner = false,
    ): string {
        $layout = Fields::createLayout($value);
        $layout->type = $elementType;
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(
            fn (): string => $withGeneratedFields || $withCardViewDesigner
                ? app(Designer::class)->fieldHtml($layout, [
                    'customizableTabs' => $customizableTabs,
                    'disabled' => $disabled,
                    'withGeneratedFields' => $withGeneratedFields,
                    'withCardViewDesigner' => $withCardViewDesigner,
                ])
                : app(Designer::class)->html($layout, [
                    'customizableTabs' => $customizableTabs,
                    'disabled' => $disabled,
                ]),
            $namespace,
        );
    }

    public function component(): string
    {
        return 'craft:field-layout-designer';
    }

    public function elementType(string $elementType): static
    {
        $this->elementType = $elementType;

        return $this;
    }

    public function customizableTabs(bool $customizableTabs = true): static
    {
        $this->customizableTabs = $customizableTabs;

        return $this;
    }

    public function withGeneratedFields(bool $withGeneratedFields = true): static
    {
        $this->withGeneratedFields = $withGeneratedFields;

        return $this;
    }

    public function withCardViewDesigner(bool $withCardViewDesigner = true): static
    {
        $this->withCardViewDesigner = $withCardViewDesigner;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->elementType === null || ! is_a($this->elementType, ElementInterface::class, true)) {
            throw new InvalidArgumentException('FieldLayoutDesigner Controls require an element type.');
        }

        return [
            'elementType' => $this->elementType,
            'customizableTabs' => $this->customizableTabs,
            'withGeneratedFields' => $this->withGeneratedFields,
            'withCardViewDesigner' => $this->withCardViewDesigner,
        ];
    }
}
