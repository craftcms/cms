<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
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

    protected string|Closure|null $title = null;

    protected string|Closure|null $icon = null;

    protected string|Closure|null $rounded = null;

    protected bool|Closure $inline = false;

    protected function tagName(): string
    {
        return 'craft-callout';
    }

    public function title(string|Closure|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    /** Icon name; the web component falls back to a variant-specific default. */
    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** @param 'all'|'start'|'end'|'none'|Closure|null $rounded */
    public function rounded(string|Closure|null $rounded): static
    {
        $this->rounded = $rounded;

        return $this;
    }

    public function inline(bool|Closure $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    /** The callout body (default slot). */
    public function content(string|Htmlable|Stringable|ViewComponent|Closure|null $content): static
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
            'title' => $this->evaluate($this->title),
            'icon' => $this->evaluate($this->icon),
            'rounded' => $this->evaluate($this->rounded),
            'inline' => (bool) $this->evaluate($this->inline),
        ];
    }
}
