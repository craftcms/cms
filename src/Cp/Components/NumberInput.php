<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use Override;

class NumberInput extends ScalarInput
{
    #[Override]
    protected string|Closure $type = 'number';

    public static function formElementType(): string
    {
        return 'craft:number-input';
    }

    #[Override]
    protected function formElementProps(): array
    {
        return [
            'type' => 'number',
            'min' => $this->portableNumber('min', $this->min),
            'max' => $this->portableNumber('max', $this->max),
            'step' => $this->portableNumber('step', $this->step),
        ];
    }
}
