<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\SelectColor as SelectColorComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

/**
 * A named-palette color Control (red/orange/.../black — the same palette as
 * {@see \CraftCms\Cms\Shared\Enums\Color}), rendered as a swatch-decorated
 * rich select. Its canonical value is a color slug string, or null.
 *
 * This is the modern replacement for the legacy `colorSelectField` Twig
 * macro — narrow the offered colors to a specific enum's cases with
 * {@see self::colors()} when a caller's values aren't drawn from the full
 * shared palette.
 */
class ColorSelect extends Control
{
    private bool $allowTransparent = false;

    private ?string $blankLabel = null;

    /** @var list<string>|null */
    private ?array $colors = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $allowTransparent = (bool) ($control->props['allowTransparent'] ?? false);

        // The underlying <craft-select-color> uses this sentinel internally for its blank
        // option's choiceValue; our own canonical "no color" value stays null/empty, matching
        // every other select-like control in this Form system, so it's translated here rather
        // than leaking out to the server.
        $componentValue = match (true) {
            $value !== null && $value !== '' => (string) $value,
            $allowTransparent => '__blank__',
            default => null,
        };

        return SelectColorComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($componentValue)
            ->allowTransparent($allowTransparent)
            ->blankLabel($control->props['blankLabel'] ?? null)
            ->colors($control->props['colors'] ?? null)
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->required($attributes['required'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:color-select';
    }

    /** Prepends a blank option, labelled "Transparent" unless {@see blankLabel()} overrides it. */
    public function allowTransparent(bool $allowTransparent = true): static
    {
        $this->allowTransparent = $allowTransparent;

        return $this;
    }

    /** Overrides the blank option's label (default "Transparent"). */
    public function blankLabel(?string $blankLabel): static
    {
        $this->blankLabel = $blankLabel;

        return $this;
    }

    /**
     * Restricts the offered colors. Omit to offer every color in the shared
     * palette.
     *
     * @param  list<string>|null  $colors
     */
    public function colors(?array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'allowTransparent' => $this->allowTransparent ?: null,
            'blankLabel' => $this->blankLabel,
            'colors' => $this->colors,
        ]);
    }
}
