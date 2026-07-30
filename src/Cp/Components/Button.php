<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasAppearance;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Cp\Concerns\HasVariant;
use CraftCms\Cms\Support\Json;
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

    protected string|Closure|null $value = null;

    protected string|Closure|null $iconPosition = null;

    /** @var array<string, mixed>|Closure|null */
    protected array|Closure|null $action = null;

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

    /** The value submitted with the form, or used for selection within a button group. */
    public function value(string|Closure|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    /** @param 'prefix'|'suffix'|Closure|null $iconPosition Where the icon renders relative to the label. */
    public function iconPosition(string|Closure|null $iconPosition): static
    {
        $this->iconPosition = $iconPosition;

        return $this;
    }

    /**
     * Declarative action to run on click — the same `runAction()` primitives
     * `craft-action-item` supports (`http`/`event`/`clipboard`/`download`),
     * serialized onto the `action` attribute.
     *
     * @param array<string, mixed>|Closure|null $action
     */
    public function action(array|Closure|null $action): static
    {
        $this->action = $action;

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
            'icon-position' => $this->evaluate($this->iconPosition),
            'loading' => (bool) $this->evaluate($this->loading),
            'active' => $this->evaluate($this->active) ? 'true' : null,
            'value' => $this->evaluate($this->value),
            'disabled' => $this->isDisabled(),
            'accessible-name' => $this->evaluate($this->accessibleName),
            'align' => $this->evaluate($this->align),
            'href' => $this->evaluate($this->href),
            'target' => $this->evaluate($this->target),
            'command' => $this->evaluate($this->command),
            'action' => ($action = $this->evaluate($this->action)) !== null
                ? Json::encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
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
