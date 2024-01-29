<?php

namespace craft\ui;

use Craft;

class ComponentAttributeBag extends \Illuminate\View\ComponentAttributeBag
{
    public function merge(array $attributeDefaults = [], $escape = true)
    {
        return parent::merge($attributeDefaults, $escape);
    }

}