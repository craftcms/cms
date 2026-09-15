<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

class ImportController
{
    public function index(Request $request): Response
    {
        $currentUser = $request->craftUser();

        return Inertia::render('import/Index', [
            'title' => t('Import'),
            'crumbs' => [
                ['label' => t('Import')],
            ],
            'canViewConfigs' => (bool) $currentUser?->can('viewImportConfigs'),
            'canViewRuns' => (bool) $currentUser?->can('viewImportRuns'),
        ]);
    }
}
