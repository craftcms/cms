<?php

namespace craft\ui\components;

use craft\helpers\Html;
use craft\ui\Component;

class MenuGroup extends Component
{
    protected array $items = [];

    protected ?string $heading = null;

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function heading(?string $heading): static
    {
        $this->heading = $heading;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getHeading(): ?string
    {
        $heading = $this->evaluate($this->heading);

        return $heading ? Html::tag('strong', $heading, [
            'id' => 'heading-' . $this->getId(),
            'class' => ['visually-hidden'],
        ]) : null;
    }
}
