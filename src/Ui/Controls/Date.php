<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

/**
 * @since 6.0.0
 */
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
