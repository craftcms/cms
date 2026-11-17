<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner as Designer;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Nodes\Outlet;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use InvalidArgumentException;

/**
 * @since 6.0.0
 */
class FieldLayoutDesigner extends Control
{
    private ?string $elementType = null;

    private bool $customizableTabs = true;

    private bool $withGeneratedFields = false;

    private bool $withCardViewDesigner = false;

    private ?string $cardViewDesignerOutlet = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return self::designerHtml(
            is_array($value) ? $value : [],
            $control->props['elementType'],
            $attributes['name'],
            $attributes['name'] === null,
            (bool) $control->props['customizableTabs'],
            (bool) $control->props['withGeneratedFields'],
            (bool) $control->props['withCardViewDesigner'],
            $control->props['cardViewDesignerOutlet'] ?? null,
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
        ?string $cardViewDesignerOutlet = null,
    ): string {
        $layout = Fields::createLayout(['type' => $elementType] + $value);
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(
            fn (): string => $withGeneratedFields || $withCardViewDesigner
                ? app(Designer::class)->fieldHtml($layout, [
                    'customizableTabs' => $customizableTabs,
                    'disabled' => $disabled,
                    'withGeneratedFields' => $withGeneratedFields,
                    'withCardViewDesigner' => $withCardViewDesigner,
                    'cardViewDesignerOutlet' => $cardViewDesignerOutlet,
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

    /**
     * Moves the card view designer out of the designer's own Appearance tab
     * and into the named {@see Outlet}, for forms
     * that give the field layout and the card appearance tabs of their own.
     * The designer then shows its Content panel alone.
     *
     * Only applies along with {@see self::withCardViewDesigner()}. Server-rendered HTML
     * shows the card view designer below the Content panel instead.
     */
    public function cardViewDesignerOutlet(?string $outlet): static
    {
        $this->cardViewDesignerOutlet = $outlet;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function emptyValue(): mixed
    {
        return [];
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
            ...($this->withCardViewDesigner && $this->cardViewDesignerOutlet !== null
                ? [
                    'cardViewDesignerOutlet' => $this->cardViewDesignerOutlet,
                    // The settings the card view designer edits, whose errors
                    // flag the outlet's tab rather than the designer's.
                    'errorOutlets' => [$this->cardViewDesignerOutlet => ['cardView', 'thumbSources']],
                ]
                : []),
        ];
    }
}
