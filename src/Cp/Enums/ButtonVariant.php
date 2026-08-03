<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * The single visual axis for a button, combining color and appearance. Mirrors
 * `ButtonVariant` in `@craftcms/cp` (src/components/button/button.ts).
 *
 * `Primary` and `Danger` are colored (accent / danger) and stable; every other
 * value uses the neutral palette in the named appearance. The button's separate
 * `inherit` flag makes the neutral variants adopt an ambient colorable palette.
 */
enum ButtonVariant: string
{
    case Primary = 'primary';
    case Danger = 'danger';
    case DangerPlain = 'danger-plain';
    case Solid = 'solid';
    case Fill = 'fill';
    case Outline = 'outline';
    case Dashed = 'dashed';
    case Plain = 'plain';
    case Link = 'link';
    case None = 'none';
}
