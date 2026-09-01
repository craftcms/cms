<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasAppearance;
use CraftCms\Cms\Cp\Concerns\HasVariant;
use CraftCms\Cms\Cp\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * PHP counterpart to the `<craft-callout>` web component.
 *
 *     Callout::make()
 *         ->variant('warning')
 *         ->title(t('Heads up'))
 *         ->content(t('Changing this may result in data loss.'));
 *
 * Renders directly (no Blade view) — the component is a single element whose
 * chrome lives in the web component. Content strings are HTML-encoded; pass
 * an `Htmlable` for trusted markup.
 */
class Callout extends ViewComponent
{
    use HasAppearance;
    use HasVariant;

    protected ?string $title = null;

    protected ?string $icon = null;

    protected bool $hideIcon = false;

    protected ?string $rounded = null;

    protected bool $inline = false;

    protected ?string $size = null;

    protected string|int|null $padding = null;

    protected function tagName(): string
    {
        return 'craft-callout';
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /** Icon name; the web component falls back to a variant-specific default. */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Suppresses the icon entirely, including the variant default. */
    public function hideIcon(bool $hideIcon = true): static
    {
        $this->hideIcon = $hideIcon;

        return $this;
    }

    /** @param 'all'|'start'|'end'|'none'|null $rounded */
    public function rounded(?string $rounded): static
    {
        $this->rounded = $rounded;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * `small` steps the type down; `auto` (the web component's default) keeps
     * it at the surrounding text size.
     *
     * Not the shared {@see Size} vocabulary — a
     * callout sizes its type relative to its surroundings rather than picking
     * from the component scale, and `auto` has no place in that enum.
     *
     * @param  'small'|'auto'|null  $size
     */
    public function size(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Spacing inside the callout: `sm`/`md`/`lg`/`xl` (mapped to
     * `--c-spacing-*`), `0`, a unitless number (pixels), or any CSS length.
     * Free-form by design, so it isn’t validated against a value set; the web
     * component has its own default.
     */
    public function padding(string|int|null $padding): static
    {
        $this->padding = $padding;

        return $this;
    }

    /** The callout body (default slot). */
    public function content(string|Htmlable|Stringable|ViewComponent|null $content): static
    {
        $this->slots[static::DEFAULT_SLOT] = $content;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'variant' => $this->getVariant(),
            'appearance' => $this->getAppearance(),
            'title' => $this->title,
            'icon' => $this->icon,
            'hide-icon' => $this->hideIcon,
            'rounded' => $this->rounded,
            'inline' => $this->inline,
            'size' => $this->size,
            'padding' => $this->padding,
        ];
    }
}
