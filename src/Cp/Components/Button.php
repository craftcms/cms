<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasAppearance;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Cp\Concerns\HasVariant;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * PHP counterpart to the `<craft-button>` web component.
 *
 *     Button::make()
 *         ->label(t('Save'))
 *         ->type('submit')
 *         ->variant('primary')
 *         ->icon('check');
 *
 * The label fills the default slot (plain strings are HTML-encoded; pass an
 * `Htmlable` for markup labels). With `href` set, the component renders as a
 * link styled as a button.
 */
class Button extends ViewComponent
{
    use HasAppearance;
    use HasDisabled;
    use HasId;
    use HasSize;
    use HasVariant;

    protected string|Closure $type = 'button';

    protected string|Closure|null $icon = null;

    protected bool|Closure $loading = false;

    protected bool|Closure $active = false;

    protected string|Closure|null $accessibleName = null;

    protected string|Closure|null $align = null;

    protected string|Closure|null $href = null;

    protected string|Closure|null $target = null;

    protected string|Closure|null $command = null;

    protected function tagName(): string
    {
        return 'craft-button';
    }

    /** @param 'button'|'submit'|'reset'|Closure $type */
    public function type(string|Closure $type): static
    {
        $this->type = $type;

        return $this;
    }

    /** The button label (default slot). Plain strings are HTML-encoded. */
    public function label(string|Htmlable|Stringable|ViewComponent|Closure|null $label): static
    {
        $this->slots[static::DEFAULT_SLOT] = $label;

        return $this;
    }

    /** Icon name, rendered by the web component before the label. */
    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Content before the label (typically an icon component). Strings are trusted HTML. */
    public function prefix(string|Htmlable|Stringable|ViewComponent|Closure|null $prefix): static
    {
        $this->slots['prefix'] = fn (): mixed => $this->trustedHtml($this->evaluate($prefix));

        return $this;
    }

    /** Content after the label (typically an icon component). Strings are trusted HTML. */
    public function suffix(string|Htmlable|Stringable|ViewComponent|Closure|null $suffix): static
    {
        $this->slots['suffix'] = fn (): mixed => $this->trustedHtml($this->evaluate($suffix));

        return $this;
    }

    public function loading(bool|Closure $loading = true): static
    {
        $this->loading = $loading;

        return $this;
    }

    /** Pressed/selected state (e.g. within a button group). */
    public function active(bool|Closure $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    /** Accessible name override, for icon-only buttons. */
    public function accessibleName(string|Closure|null $accessibleName): static
    {
        $this->accessibleName = $accessibleName;

        return $this;
    }

    /** @param 'start'|'center'|'end'|Closure|null $align */
    public function align(string|Closure|null $align): static
    {
        $this->align = $align;

        return $this;
    }

    /** Renders the button as a link. */
    public function href(string|Closure|null $href, string|Closure|null $target = null): static
    {
        $this->href = $href;

        if ($target !== null) {
            $this->target = $target;
        }

        return $this;
    }

    /** Invoker Commands API command (e.g. `--add-row`). */
    public function command(string|Closure|null $command): static
    {
        $this->command = $command;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $type = (string) $this->evaluate($this->type);

        return [
            'id' => $this->getId(),
            'type' => $this->evaluate($this->href) === null ? $type : null,
            'variant' => $this->getVariant(),
            'appearance' => $this->getAppearance(),
            'size' => $this->getSize(),
            'icon' => $this->evaluate($this->icon),
            'loading' => (bool) $this->evaluate($this->loading),
            'active' => $this->evaluate($this->active) ? 'true' : null,
            'disabled' => $this->isDisabled(),
            'accessible-name' => $this->evaluate($this->accessibleName),
            'align' => $this->evaluate($this->align),
            'href' => $this->evaluate($this->href),
            'target' => $this->evaluate($this->target),
            'command' => $this->evaluate($this->command),
        ];
    }

    /** Strings on trusted-HTML slots render unencoded, matching FormFields. */
    private function trustedHtml(mixed $value): mixed
    {
        if (is_string($value) || ($value instanceof Stringable && ! $value instanceof Htmlable && ! $value instanceof ViewComponent)) {
            return new HtmlString((string) $value);
        }

        return $value;
    }
}
