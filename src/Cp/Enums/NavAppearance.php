<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * How a `<craft-nav-list>` lays its items out. Mirrors `NavAppearance` in
 * `@craftcms/ui` (src/constants/nav-appearances.ts).
 */
enum NavAppearance: string
{
    /** A column of rows. */
    case List = 'list';

    /**
     * A horizontal strip, ruled off from the content below it and marking the
     * current page with a bar on that rule.
     */
    case Tabs = 'tabs';
}
