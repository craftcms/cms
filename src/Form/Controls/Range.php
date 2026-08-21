<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

class Range extends Text
{
    #[\Override]
    protected string $inputType = 'range';

    #[\Override]
    public function component(): string
    {
        return 'craft:range';
    }
}
