<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Which axis a `<craft-tabs>` strip runs along. Mirrors `TabsLayout` in
 * `@craftcms/ui` (src/components/tabs/tabs.ts).
 */
enum TabsLayout: string
{
    case Horizontal = 'horizontal';
    case Vertical = 'vertical';
}
