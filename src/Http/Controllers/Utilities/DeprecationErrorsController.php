<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\web\Application;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class DeprecationErrorsController
{
    public function __construct(
        Utilities $utilitiesService,
        private Deprecator $deprecator,
        #[Give('Craft')] private Application $craft
    ) {
        abort_unless($utilitiesService->checkAuthorization(DeprecationErrors::class), 403, 'User is not authorized to perform this action.');
    }

    public function getDeprecationErrorTracesModal(Request $request): JsonResponse
    {
        $request->validate([
            'logId' => ['required', 'integer', Rule::exists(Table::DEPRECATIONERRORS, 'id')],
        ]);

        $html = $this->craft->getView()->renderTemplate('_components/utilities/DeprecationErrors/traces_modal.twig', [
            'log' => $this->deprecator->getLogById($request->integer('logId')),
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    public function deleteDeprecationError(Request $request): JsonResponse
    {
        $request->validate([
            'logId' => ['required', 'integer', Rule::exists(Table::DEPRECATIONERRORS, 'id')],
        ]);

        $this->deprecator->deleteLogById($request->integer('logId'));

        return new JsonResponse;
    }

    public function deleteAllDeprecationErrors(): JsonResponse
    {
        $this->deprecator->deleteAllLogs();

        return new JsonResponse;
    }
}
