<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;

class TimeInput extends ScalarInput
{
    protected string|Closure $type = 'time';

    public static function formElementType(): string
    {
        return 'craft:time-input';
    }
}
