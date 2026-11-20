<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Http\Controllers\Import\ImportRunsController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Answers the run page's requests for the made-up runs' items, which have no record to look up, and leaves real runs
 * to the control panel.
 */
class WorkbenchImportRunsController extends ImportRunsController
{
    public function items(Request $request, string $runId): JsonResponse
    {
        return ImportRunPagesController::isMadeUp($runId)
            ? app(ImportRunPagesController::class)->items($request, $runId)
            : parent::items($request, $runId);
    }

    public function itemData(Request $request, string $runId): JsonResponse
    {
        return ImportRunPagesController::isMadeUp($runId)
            ? app(ImportRunPagesController::class)->itemData($request, $runId)
            : parent::itemData($request, $runId);
    }
}
