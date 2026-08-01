<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class ColorPaletteInput extends InputElement
{
    public static function type(): string
    {
        return 'craft:color-palette-input';
    }
}
