<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

class Date extends Text
{
    #[\Override]
    protected string $inputType = 'date';

    #[\Override]
    public function component(): string
    {
        return 'craft:date';
    }
}
