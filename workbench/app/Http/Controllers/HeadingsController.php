<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Http\Responses\CpScreenResponse;

/**
 * Every `.cp-h*` heading class, on its own and in a typical settings screen.
 */
class HeadingsController
{
    public function __invoke(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title('Headings')
            ->addCrumb('Workbench', 'workbench/headings')
            ->inertiaPage('workbench/Headings');
    }
}
