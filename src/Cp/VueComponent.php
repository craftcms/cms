<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Illuminate\View\ComponentAttributeBag;

class VueComponent
{
    public static function render(string $name, $props = [])
    {
        $attributes = new ComponentAttributeBag($props);

        return "<$name $attributes />";
    }
}
