<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class TimeInput extends InputElement
{
    public static function type(): string
    {
        return 'craft:time-input';
    }
}
