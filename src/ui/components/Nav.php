<?php

namespace craft\ui\components;

use craft\helpers\Cp;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use Illuminate\Support\Collection;

class Nav
{
    use HasId;
    use HasLabel;

    protected array $items = [];

    public function __construct()
    {
        // TODO: not sure this is the best place for this kind of thing. Probably should be in a "setup" method of some kind.
        $this->id('nav');
    }

    public static function make(): self
    {
        return new self();
    }

    public function items(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function getItems(): Collection
    {
        return Collection::make($this->items)
            ->map(function($item) {
                if ($item instanceof NavItem) {
                    return $item->render();
                }

                return NavItem::make()->render();
            });
    }

    public function render(): string
    {
        return Cp::renderTemplate('_ui/nav.twig', [
            'items' => $this->getItems(),
            'id' => $this->getId(),
            'label' => $this->getLabel(),
        ]);
    }
}
