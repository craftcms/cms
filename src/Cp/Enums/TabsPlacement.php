<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Where a `<craft-tabs>` strip sits relative to its panels. Mirrors
 * `TabsPlacement` in `@craftcms/ui` (src/components/tabs/tabs.ts).
 *
 * Logical rather than physical, so an inline-start strip lands on the left in
 * LTR and on the right in RTL without anything special-casing it. Supersedes
 * {@see TabsLayout}, which only described the axis.
 */
enum TabsPlacement: string
{
    case BlockStart = 'block-start';
    case BlockEnd = 'block-end';
    case InlineStart = 'inline-start';
    case InlineEnd = 'inline-end';
}
