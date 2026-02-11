<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

class VueComponent
{
    public static function render(string $name, $props = []): string
    {
        $attributes = new VueProps($props);

        return "<$name $attributes />";
    }
}
