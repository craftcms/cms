<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;

class DateInput extends ScalarInput
{
    protected string|Closure $type = 'date';

    public static function formElementType(): string
    {
        return 'craft:date-input';
    }
}
