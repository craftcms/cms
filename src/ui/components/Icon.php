<?php

namespace craft\ui\components;

use craft\helpers\Cp;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;

class Icon extends Component
{
    use HasLabel;

    protected ?string $icon = null;

    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function render(): string
    {
        return Html::tag('span', Cp::iconSvg($this->getIcon(), $this->getLabel()), [
            'class' => 'icon icon-mask',
            'aria-hidden' => 'true',
        ]);
    }
}
