<?php

namespace craft\ui\components;

use Closure;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasId;

class DisclosureMenu extends Component
{
    use HasId;

    protected string|Closure|Button|null $button = null;
    protected string|Closure|Menu|null $menu = null;

    public function button(string|Closure|Button|null $value = ''): static
    {
        $this->button = $value;
        return $this;
    }

    public function menu(string|Closure|Menu|null $value): static
    {
        $this->menu = $value;
        return $this;
    }

    public function renderContents(): string
    {
        return $this->getButton() . $this->getMenu();
    }

    public function getButton(): string|Closure|Button|null
    {
        $button = $this->evaluate($this->button);

        $attributes = [
            'class' => ['menubtn'],
            'data-trigger' => 'true',
            'aria-controls' => 'menu-' . $this->getId(),
            'data-disclosure-trigger' => 'true',
        ];

        if ($button instanceof Component) {
            return $button
                ->attributes($attributes)
                ->render();
        }

        if (is_string($this->button)) {
            return Button::make()
                ->attributes($attributes)
                ->label($this->button)
                ->render();
        }

        return Button::make()->attributes($attributes)->render();
    }

    public function render(): string
    {
        return Html::tag('div', $this->renderContents(), $this->getAttributes());
    }

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes());
    }

    public function getMenu(): ?string
    {
        $menu = $this->evaluate($this->menu);

        if (!$menu instanceof Menu) {
            $menu = Menu::make();
        }

        return $menu
            ->attributes([
                'id' => 'menu-' . $this->getId(),
                'class' => ['menu', 'menu--disclosure'],
            ])
            ->id('menu-' . $this->getId())
            ->render();
    }
}
