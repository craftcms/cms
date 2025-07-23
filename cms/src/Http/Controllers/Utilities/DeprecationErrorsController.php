<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeprecationErrorsController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(Utilities\DeprecationErrors::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function getDeprecationErrorTracesModal(Request $request): JsonResponse
    {
        $logId = $request->validate([
            'logId' => ['required', 'integer', Rule::exists('deprecationerrors', 'id')],
        ])['logId'];

        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        $html = $craft->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal.twig', [
            'log' => $craft->deprecator->getLogById($logId),
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    public function deleteDeprecationError(Request $request): JsonResponse
    {
        $logId = $request->validate([
            'logId' => ['required', 'integer', Rule::exists('deprecationerrors', 'id')],
        ])['logId'];

        /** @var \craft\web\Application $craft */
        $craft = app('Craft');
        $craft->deprecator->deleteLogById($logId);

        return new JsonResponse;
    }

    public function deleteAllDeprecationErrors(): JsonResponse
    {
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');
        $craft->deprecator->deleteAllLogs();

        return new JsonResponse;
    }
}
