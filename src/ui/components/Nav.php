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

    /**
     * @var string|null ID of the selected item
     */
    protected ?string $selectedItem = null;

    public function __construct()
    {
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
                if (!($item instanceof NavItem)) {
                    $item = NavItem::make()
                        ->withProps($item);
                }

                if ($this->getSelectedItem()) {
                    $item->selected($item->getId() === $this->getSelectedItem());
                }

                return $item->render();
            });
    }

    public function selectedItem(?string $item): self
    {
        $this->selectedItem = $item;
        return $this;
    }

    public function getSelectedItem(): ?string
    {
        return $this->selectedItem;
    }

    public function render(): string
    {
        return Cp::renderTemplate('_ui/nav.twig', [
            'items' => $this->getItems(),
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'selectedItem' => $this->getSelectedItem(),
        ]);
    }
}
