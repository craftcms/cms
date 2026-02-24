<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

final readonly class DeprecationErrorsController
{
    public function __construct(
        Utilities $utilitiesService,
        private Deprecator $deprecator
    ) {
        if (! $utilitiesService->checkAuthorization(DeprecationErrors::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function getDeprecationErrorTracesModal(Request $request): JsonResponse
    {
        $request->validate([
            'logId' => ['required', 'integer', Rule::exists(Table::DEPRECATIONERRORS, 'id')],
        ]);

        $html = template('_components/utilities/DeprecationErrors/traces_modal', [
            'log' => $this->deprecator->getLogById($request->integer('logId')),
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    public function deleteDeprecationError(Request $request): Response
    {
        $request->validate([
            'logId' => ['required', 'integer', Rule::exists(Table::DEPRECATIONERRORS, 'id')],
        ]);

        $this->deprecator->deleteLogById($request->integer('logId'));

        if ($request->inertia()) {
            return back()->with('success', t('Deprecation error removed.'));
        }

        return new JsonResponse;
    }

    public function deleteAllDeprecationErrors(Request $request): Response
    {
        $this->deprecator->deleteAllLogs();

        if ($request->inertia()) {
            return back()->with('success', t('Deprecation errors removed.'));
        }

        return new JsonResponse;
    }
}
