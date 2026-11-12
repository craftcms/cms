<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Enums\NavAppearance;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * PHP counterpart to the `<craft-nav-item>` web component — one row of a
 * {@see NavList}:
 *
 *     NavItem::make()
 *         ->label(t('Feeds'))
 *         ->url('/admin/feed-me/feeds')
 *         ->current();
 *
 * Renders directly (no Blade view) — the chrome lives in the web component.
 *
 * Covers the row as a nav strip needs it. The web component also does rails,
 * subnavs and flyouts, so registering this with {@see ComponentRegistry} means
 * covering those first.
 *
 * @since 6.0.0
 */
class NavItem extends ViewComponent
{
    protected ?string $url = null;

    protected ?string $icon = null;

    protected bool $active = false;

    protected bool $current = false;

    protected NavAppearance|string|null $appearance = null;

    protected function tagName(): string
    {
        return 'craft-nav-item';
    }

    /** The row's label (default slot). */
    public function label(string|Htmlable|Stringable|ViewComponent|null $label): static
    {
        $this->slots[static::DEFAULT_SLOT] = $label;

        return $this;
    }

    /** An icon shown before the label. */
    public function icon(?string $icon): static
    {
        $this->icon = $icon !== '' ? $icon : null;

        return $this;
    }

    /** Where the row goes. Without one it renders as a plain label. */
    public function url(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * On the trail to the page you're on: the page itself, or an ancestor of
     * it. {@see self::current()} marks the end of that trail.
     */
    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    /** The page you're on, as opposed to an ancestor of it. */
    public function current(bool $current = true): static
    {
        $this->current = $current;

        return $this;
    }

    /**
     * How the row is drawn. Set by the {@see NavList} holding it, which owns
     * the layout both halves of the strip have to agree on.
     */
    public function appearance(NavAppearance|string|null $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function getAppearance(): ?string
    {
        if ($this->appearance === null) {
            return null;
        }

        return $this->appearance instanceof NavAppearance
            ? $this->appearance->value
            : NavAppearance::from($this->appearance)->value;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'icon' => $this->icon,
            'href' => $this->url,
            'active' => $this->active || $this->current,
            'current' => $this->current,
            'appearance' => $this->getAppearance(),
        ];
    }
}
