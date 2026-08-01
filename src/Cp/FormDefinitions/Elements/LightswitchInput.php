<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class LightswitchInput extends InputElement
{
    public static function type(): string
    {
        return 'craft:lightswitch-input';
    }
}
