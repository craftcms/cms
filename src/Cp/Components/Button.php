<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Cp\Enums\ButtonVariant;
use CraftCms\Cms\Cp\Icons;
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
    use HasDisabled;
    use HasId;
    use HasSize;

    protected ButtonVariant|string|null $variant = null;

    protected bool $inherit = false;

    protected string $type = 'button';

    protected ?string $icon = null;

    protected bool $loading = false;

    protected bool $active = false;

    protected ?string $accessibleName = null;

    protected ?string $align = null;

    protected ?string $href = null;

    protected ?string $target = null;

    protected ?string $command = null;

    protected ?string $value = null;

    protected ?string $iconPosition = null;

    /** @var array<string, mixed>|null */
    protected ?array $action = null;

    protected function tagName(): string
    {
        return 'craft-button';
    }

    /** @param 'button'|'submit'|'reset' $type */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /** The button's visual style (the single variant axis). */
    public function variant(ButtonVariant|string|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): ?string
    {
        if ($this->variant === null) {
            return null;
        }

        return $this->variant instanceof ButtonVariant
            ? $this->variant->value
            : ButtonVariant::from($this->variant)->value;
    }

    /**
     * Adopt the ambient colorable palette (from a colorable ancestor, e.g. a
     * callout) instead of the neutral palette. Only affects the neutral
     * variants; `primary` and `danger` stay stable.
     */
    public function inherit(bool $inherit = true): static
    {
        $this->inherit = $inherit;

        return $this;
    }

    /** The button label (default slot). Plain strings are HTML-encoded. */
    public function label(string|Htmlable|Stringable|ViewComponent|null $label): static
    {
        $this->slots[static::DEFAULT_SLOT] = $label;

        return $this;
    }

    /** Icon name, rendered by the web component before the label. */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Content before the label (typically an icon component). Strings are trusted HTML. */
    public function prefix(string|Htmlable|Stringable|ViewComponent|null $prefix): static
    {
        $this->slots['prefix'] = $this->trustedHtml($prefix);

        return $this;
    }

    /** Content after the label (typically an icon component). Strings are trusted HTML. */
    public function suffix(string|Htmlable|Stringable|ViewComponent|null $suffix): static
    {
        $this->slots['suffix'] = $this->trustedHtml($suffix);

        return $this;
    }

    public function loading(bool $loading = true): static
    {
        $this->loading = $loading;

        return $this;
    }

    /** Pressed/selected state (e.g. within a button group). */
    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    /** Accessible name for an icon-only button. Rendered as `aria-label`. */
    public function accessibleName(?string $accessibleName): static
    {
        $this->accessibleName = $accessibleName;

        return $this;
    }

    /** @param 'start'|'center'|'end'|null $align */
    public function align(?string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /** Renders the button as a link. */
    public function href(?string $href, ?string $target = null): static
    {
        $this->href = $href;

        if ($target !== null) {
            $this->target = $target;
        }

        return $this;
    }

    /** Invoker Commands API command (e.g. `--add-row`). */
    public function command(?string $command): static
    {
        $this->command = $command;

        return $this;
    }

    /** The value submitted with the form, or used for selection within a button group. */
    public function value(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /** @param 'prefix'|'suffix'|null $iconPosition Where the icon renders relative to the label. */
    public function iconPosition(?string $iconPosition): static
    {
        $this->iconPosition = $iconPosition;

        return $this;
    }

    /**
     * Declarative action to run on click — the same `runAction()` primitives
     * `craft-action-item` supports (`http`/`event`/`clipboard`/`download`),
     * serialized onto the `action` attribute.
     *
     * @param  array<string, mixed>|null  $action
     */
    public function action(?array $action): static
    {
        $this->action = $action;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        // Legacy aliases and custom icons are resolved here, same as `Icon`
        // — `<craft-button>`'s `icon` is a single name attribute (no separate
        // `family`), so a non-`solid` family is folded into it as a prefix.
        $icon = null;
        if ($this->icon !== null) {
            $resolvedIcon = Icons::resolveIconData($this->icon);
            $icon = $resolvedIcon['family'] !== 'solid'
                ? "{$resolvedIcon['family']}/{$resolvedIcon['name']}"
                : $resolvedIcon['name'];
        }

        return [
            'id' => $this->getId(),
            'type' => $this->href === null ? $this->type : null,
            'variant' => $this->getVariant(),
            'inherit' => $this->inherit,
            'size' => $this->getSize(),
            'icon' => $icon,
            'icon-position' => $this->iconPosition,
            'loading' => $this->loading,
            'active' => $this->active ? 'true' : null,
            'value' => $this->value,
            'disabled' => $this->isDisabled(),
            'aria-label' => $this->accessibleName,
            'align' => $this->align,
            'href' => $this->href,
            'target' => $this->target,
            'command' => $this->command,
            'action' => $this->action !== null
                ? Json::encode($this->action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
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
