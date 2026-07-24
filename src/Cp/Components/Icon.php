<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Shared\Enums\Color;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * PHP counterpart to the `<craft-icon>` web component.
 *
 *     Icon::make()
 *         ->name('pencil')
 *         ->label(t('Edited'));
 *
 * Icon names accept the legacy aliases (`settings`, `edit`, …) and custom
 * icons, resolved through {@see Icons::resolveIconData()} at render time —
 * the same mapping the rest of the CP uses — so `name('move')` renders
 * `name="grip-dots" family="custom-icons"`. An explicit {@see self::family()}
 * wins over the resolved one.
 *
 * `color()` renders as the `data-color` attribute, which scopes the
 * `--c-color-*` tokens the icon's styles consume (e.g. the `badge`
 * appearance).
 */
class Icon extends ViewComponent
{
    protected string|Closure|null $name = null;

    protected string|Closure|null $family = null;

    protected string|Closure|null $variant = null;

    protected string|Closure|null $label = null;

    protected string|Closure|null $appearance = null;

    protected Color|string|Closure|null $color = null;

    protected function tagName(): string
    {
        return 'craft-icon';
    }

    /** Icon name; legacy aliases and custom icons are resolved at render time. */
    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** Icon family; overrides the family resolved from the name. */
    public function family(string|Closure|null $family): static
    {
        $this->family = $family;

        return $this;
    }

    public function variant(string|Closure|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * A description of the icon for assistive devices. Without one, the web
     * component treats the icon as presentational.
     */
    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** `plain` (default) or `badge`. */
    public function appearance(string|Closure|null $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    /**
     * Palette for the icon, rendered as the `data-color` attribute that
     * scopes the `--c-color-*` tokens.
     */
    public function color(Color|string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Inline SVG markup (trusted, not encoded) rendered in the default slot.
     * When slotted content is present, the web component suppresses the
     * name-based icon. Pairs with {@see Icons::svg()}.
     */
    public function svg(string|Stringable|Htmlable|Closure|null $svg): static
    {
        $this->slots[static::DEFAULT_SLOT] = $svg === null ? null : function () use ($svg) {
            $svg = $this->evaluate($svg);

            if ($svg === null) {
                return null;
            }

            return $svg instanceof Htmlable ? $svg : new HtmlString((string) $svg);
        };

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $name = $this->evaluate($this->name);
        $resolved = $name !== null ? Icons::resolveIconData((string) $name) : null;
        $color = $this->evaluate($this->color);

        return [
            'name' => $resolved['name'] ?? null,
            'family' => $this->evaluate($this->family) ?? $resolved['family'] ?? null,
            'variant' => $this->evaluate($this->variant),
            'label' => $this->evaluate($this->label),
            'appearance' => $this->evaluate($this->appearance),
            'data-color' => $color instanceof Color ? $color->value : $color,
        ];
    }
}
