<?php

declare(strict_types=1);

namespace Workbench\App\Widgets;

use CraftCms\Cms\Dashboard\Widgets\Widget;
use Override;

/**
 * A plugin widget on a core page. Its Vue component tries to fill the
 * Dashboard's `content-actions` slot and is refused: not its page.
 */
class LayoutSlotsDemo extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return 'Layout Slots Demo';
    }

    #[Override]
    public function component(): string
    {
        return 'workbench:layout-slots-demo-widget';
    }
}
