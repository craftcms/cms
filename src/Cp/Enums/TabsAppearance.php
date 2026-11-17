<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * How a `<craft-tabs>` strip is drawn. Mirrors `TabsAppearance` in
 * `@craftcms/ui` (src/components/tabs/tabs.ts).
 *
 * @since 6.0.0
 */
enum TabsAppearance: string
{
    /** Ruled off from the panels, with the selected tab underlined. */
    case Line = 'line';

    /** On a quiet track, with the selected tab raised out of it. */
    case Fill = 'fill';
}
