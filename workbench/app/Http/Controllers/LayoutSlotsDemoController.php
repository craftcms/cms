<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;

/**
 * A plugin-owned CP page that fills the screen's layout slots through the
 * global `craft:layout-slot` component. See `workbench/resources/js/layout-slots-demo`.
 */
class LayoutSlotsDemoController
{
    public function __invoke(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title('Layout Slots')
            ->addCrumb('Workbench', 'workbench/layout-slots')
            ->subnav([
                new NavItem()->label('Overview')->href('workbench/layout-slots'),
                new NavItem()->label('Dashboard widget')->href('dashboard'),
            ])
            ->inertiaPage('workbench/LayoutSlotsDemo', [
                'owner' => 'Layout Slots Demo plugin',
            ]);
    }
}
