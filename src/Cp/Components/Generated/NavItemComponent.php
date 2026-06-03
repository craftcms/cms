<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components\Generated;

use Closure;
use CraftCms\Cms\Cp\Components\ViewComponent;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * A navigation item, rendered as a link within a nav list, optionally
 * with a collapsible subnav, a prefix icon/indicator, and an icon-only mode.
 *
 * @generated from the `craft-nav-item` custom element. Do not edit by hand.
 *           Run `npm run generate:php` in packages/craftcms-cp to regenerate.
 *           Add behavior in the concrete subclass, not here.
 */
abstract class NavItemComponent extends ViewComponent
{
    protected ?string $icon = null;

    protected ?string $href = null;

    protected bool $active = false;

    protected bool $external = false;

    protected bool $indicator = false;

    protected ?string $id = null;

    protected bool $iconOnly = false;

    protected bool $flush = false;

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function href(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function active(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function external(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    public function indicator(bool $indicator): static
    {
        $this->indicator = $indicator;

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function iconOnly(bool $iconOnly): static
    {
        $this->iconOnly = $iconOnly;

        return $this;
    }

    public function flush(bool $flush): static
    {
        $this->flush = $flush;

        return $this;
    }

    public function content(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots[''] = $content;

        return $this;
    }

    public function prefix(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['prefix'] = $content;

        return $this;
    }

    public function iconSlot(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['icon'] = $content;

        return $this;
    }

    public function suffix(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['suffix'] = $content;

        return $this;
    }

    public function subnav(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['subnav'] = $content;

        return $this;
    }

    protected function tagName(): string
    {
        return 'craft-nav-item';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'icon' => $this->icon,
            'href' => $this->href,
            'active' => $this->active === false ? null : $this->active,
            'external' => $this->external === false ? null : $this->external,
            'indicator' => $this->indicator === false ? null : $this->indicator,
            'id' => $this->id,
            'icon-only' => $this->iconOnly === false ? null : $this->iconOnly,
            'flush' => $this->flush === false ? null : $this->flush,
        ];
    }
}
