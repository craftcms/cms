<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;

class DateInput extends ScalarInput
{
    #[\Override]
    protected string|Closure $type = 'date';

    public static function formElementType(): string
    {
        return 'craft:date-input';
    }

    /** @return array<string, mixed> */
    #[\Override]
    protected function formElementProps(): array
    {
        return ['type' => 'date'];
    }
}
