<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

/**
 * @since 6.0.0
 */
class Time extends Text
{
    #[\Override]
    protected string $inputType = 'time';

    #[\Override]
    public function component(): string
    {
        return 'craft:time';
    }
}
