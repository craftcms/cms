<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class DateInput extends InputElement
{
    public static function type(): string
    {
        return 'craft:date-input';
    }
}
