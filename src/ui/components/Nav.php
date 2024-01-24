<?php

namespace craft\ui\components;

use craft\ui\Component;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use Illuminate\Support\Collection;

class Nav extends Component
{
    use HasId;
    use HasLabel;

    protected string $view = '_ui/nav.twig';

    protected array $items = [];

    /**
     * @var string|null ID of the selected item
     */
    protected ?string $selectedItem = null;

    public function items(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function getItems(): Collection
    {
        return Collection::make($this->items)
            ->map(function(array|NavItem $config) {
                if (is_array($config)) {
                    $item = NavItem::make();

                    foreach ($config as $key => $value) {
                        $item->$key($value);
                    }
                } else {
                    $item = $config;
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
}
