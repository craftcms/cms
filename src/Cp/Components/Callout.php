<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasAppearance;
use CraftCms\Cms\Cp\Concerns\HasVariant;
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
        ];
    }
}
