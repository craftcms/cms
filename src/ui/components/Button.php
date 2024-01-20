<?php

namespace craft\ui\components;

use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;

class Button extends Component
{
    use HasLabel;

    public function render(): string
    {
        return Html::button($this->getLabel(), $this->getAttributes());
    }

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes(), [
            'class' => [
                'btn',
            ],
        ]);
    }
}
