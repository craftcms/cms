<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Enums\NavAppearance;

/**
 * PHP counterpart to the `<craft-nav-list>` web component — a run of
 * {@see NavItem}s in a real list:
 *
 *     NavList::make()
 *         ->appearance(NavAppearance::Tabs)
 *         ->item(NavItem::make()->label(t('Feeds'))->url($url)->current());
 *
 * The appearance is set on the list and passed down to its items, so the
 * server sends the strip whole rather than leaving the web component to pass it
 * along on its first render.
 *
 * Renders directly (no Blade view) — the chrome lives in the web component.
 *
 * @since 6.0.0
 */
class NavList extends ViewComponent
{
    /** @var list<NavItem> */
    protected array $items = [];

    protected NavAppearance|string|null $appearance = null;

    protected function tagName(): string
    {
        return 'craft-nav-list';
    }

    /** Adds a row. Accepts a prepared {@see NavItem} or a plain label. */
    public function item(NavItem|string $item): static
    {
        $this->items[] = $item instanceof NavItem
            ? $item
            : NavItem::make()->label($item);

        return $this;
    }

    /**
     * Adds several rows.
     *
     * @param  iterable<NavItem|string>  $items
     */
    public function items(iterable $items): static
    {
        foreach ($items as $item) {
            $this->item($item);
        }

        return $this;
    }

    /**
     * How the list is laid out: a column of rows, or a horizontal strip. A
     * strip is a nav all the same — its items are links to other pages — so a
     * strip that switches between views of one page is {@see Tabs}.
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
            'appearance' => $this->getAppearance(),
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $appearance = $this->getAppearance();
        $html = '';

        foreach ($this->items as $item) {
            $html .= $item->appearance($appearance)->toHtml();
        }

        return $html.parent::renderSlots();
    }
}
