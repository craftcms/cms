<?php

namespace craft\ui\components;

use Closure;
use craft\enums\MenuItemType;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use craft\ui\concerns\HasPrefix;
use craft\ui\concerns\HasSuffix;
use craft\ui\concerns\Selectable;

class MenuItem extends Component
{
    use HasLabel;
    use HasId;
    use HasPrefix;
    use HasSuffix;
    use Selectable;

    protected string $view = '_ui/menu-item.twig';
    protected string|Closure|null $icon = null;
    protected string $role = 'menuitem';
    protected bool $destructive = false;
    protected ?string $tag = null;

    protected bool $hidden = false;

    protected ?string $confirm = null;

    protected MenuItemType $type = MenuItemType::Link;

    public function destructive(bool $value = true): static
    {
        $this->destructive = $value;
        return $this;
    }

    public function confirm(?string $message = null): static
    {
        $this->confirm = $message;
        return $this;
    }

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function type(MenuItemType|string $type): static
    {
        if (is_string($type)) {
            $type = MenuItemType::from($type);
        }

        $this->type = $type;
        return $this;
    }

    public function render(): string
    {
        return Html::tag($this->getTag(), $this->getContent(), $this->getAttributes());
    }

    public function tag(?string $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        if ($this->tag) {
            return $this->tag;
        }


        return match ($this->getType()) {
            MenuItemType::Button => 'button',
            MenuItemType::Link => 'a',
            default => 'div',
        };
    }

    public function getType(): MenuItemType
    {
        return $this->type;
    }

    public function getContent(): ?string
    {
        return $this->getPrefix() . $this->getLabel() . $this->getSuffix();
    }

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes(), [
            'id' => $this->getId(),
            'class' => [
                'menu-item',
                $this->getSelected() ? $this->selectedClass : null,
            ],
            'role' => $this->role,
            'aria-hidden' => $this->getHidden() ? "true" : null,
            'data' => [
                'icon' => $this->getIcon(),
                'destructive' => $this->getDestructive(),
                'confirm' => $this->getConfirm(),
            ],
        ]);
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon);
    }

    public function getDestructive(): bool
    {
        return $this->destructive;
    }

    public function getConfirm(): ?string
    {
        return $this->confirm;
    }
}
