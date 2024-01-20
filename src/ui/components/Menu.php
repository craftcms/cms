<?php

namespace craft\ui\components;

use craft\ui\Component;
use Illuminate\Support\Collection;

class Menu extends Component
{
    protected array $items = [];
    protected array $groups = [];
    protected string $view = '_ui/menu.twig';
    protected bool $visible = false;

    public function visible(bool $value = true): static
    {
        $this->visible = $value;
        return $this;
    }

    public function groups(array $groups): static
    {
        $this->groups = [
            ...$this->groups,
            ...$groups,
        ];

        return $this;
    }

    public function group(string|MenuGroup $group, array $items = []): static
    {
        if ($group instanceof MenuGroup) {
            $this->groups[] = $group;

            return $this;
        }

        $this->groups[] = MenuGroup::make()
            ->heading($group)
            ->items($items);

        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function getGroups(): array
    {
        // Get all the existing groups
        $groups = Collection::make($this->groups)
            ->filter(function(MenuGroup $group): bool {
                $visibleGroupItems = array_filter(
                    $group->getItems(),
                    fn(MenuItem $item) => !$item->getHidden()
                );

                if (empty($visibleGroupItems)) {
                    return false;
                }

                $group->items($visibleGroupItems);
                return true;
            });

        // Collect safe items
        if ($this->getSafeItems()->count()) {
            $groups->prepend(
                MenuGroup::make()
                    ->items($this->getSafeItems()->all())
            );
        }

        // Make sure destructive items are at the end
        if ($this->getDestructiveItems()->count()) {
            $groups->push(MenuGroup::make()
                ->items($this->getDestructiveItems()->all())
            );
        }

        return $groups->all();
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getSafeItems(): Collection
    {
        return $this->getVisibleMenuItems()
            ->filter(fn(MenuItem $item) => !$item->getDestructive());
    }

    public function getVisibleMenuItems(): Collection
    {
        return $this->getMenuItems()
            ->filter(fn(MenuItem $item) => !$item->getHidden());
    }

    public function getMenuItems(): Collection
    {
        return Collection::make($this->getItems())
            ->filter(fn(mixed $item) => $item instanceof MenuItem);
    }

    public function getDestructiveItems(): Collection
    {
        return $this->getVisibleMenuItems()
            ->filter(fn(MenuItem $item) => $item->getDestructive());
    }

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes(), [
            'class' => [
                'menu',
                $this->getVisible() ? 'visible' : null,
            ],
        ]);
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }
}
