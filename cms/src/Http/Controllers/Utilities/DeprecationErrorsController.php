<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\web\Application;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeprecationErrorsController
{
    public function __construct(
        Utilities $utilitiesService,
        #[Give('Craft')] protected Application $craft
    ) {
        if (! $utilitiesService->checkAuthorization(Utilities\DeprecationErrors::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function getDeprecationErrorTracesModal(Request $request): JsonResponse
    {
        $logId = $request->validate([
            'logId' => ['required', 'integer', Rule::exists('deprecationerrors', 'id')],
        ])['logId'];

        $html = $this->craft->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal.twig', [
            'log' => $this->craft->deprecator->getLogById($logId),
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

        $this->craft->deprecator->deleteLogById($logId);

        return new JsonResponse;
    }

    public function deleteAllDeprecationErrors(): JsonResponse
    {
        $this->craft->deprecator->deleteAllLogs();

        return new JsonResponse;
    }
}
