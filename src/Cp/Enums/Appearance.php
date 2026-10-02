<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Visual appearances shared across the CP component system. Mirrors the
 * `Appearance` constants in `@craftcms/cp` (src/constants/appearances.ts).
 */
enum Appearance: string
{
    case Solid = 'solid';
    case OutlineFill = 'outline-fill';
    case Fill = 'fill';
    case Outline = 'outline';
    case Plain = 'plain';
}
