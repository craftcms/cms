<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\Html;
use craft\ui\Component;
use Illuminate\View\ComponentAttributeBag;

class Textarea extends Input
{
    public function render(): string
    {
        $attributes = (new ComponentAttributeBag())
            ->class([
                'text',
                'fullwidth',
                ($this->getCode() ? 'code' : null),
                ($this->getUiMode() === 'enlarged' ? 'readable' : null)
            ])
            ->merge([
                'id' => $this->getId(),
                'data-component' => $this->getHandle()
            ])
            ->merge($this->getExtraAttributes());
        
        return Html::textarea(
            $this->getName(),
            $this->getValue(),
            $attributes->getAttributes()
        );
    }

}