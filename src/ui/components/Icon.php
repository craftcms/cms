<?php

namespace craft\ui\components;

use craft\helpers\Cp;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;
use Filament\Support\Concerns\HasIcon;
use Illuminate\View\ComponentAttributeBag;

class Icon extends Component
{
    use HasLabel;
    use HasIcon;

    public function render(): string
    {
        $attributes = (new ComponentAttributeBag())
            ->class('icon icon-mask')
            ->merge(['aria-hidden' => 'true']);

        return Html::tag('span', Cp::iconSvg($this->getIcon(), $this->getLabel()), $attributes->getAttributes());
    }
}
