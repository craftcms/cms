<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class DeprecationErrorsController
{
    use RespondsWithFlash;

    public function __construct(
        Utilities $utilitiesService,
        private Deprecator $deprecator
    ) {
        if (! $utilitiesService->checkAuthorization(DeprecationErrors::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function show(Request $request, int $logId): JsonResponse
    {
        $log = $this->deprecator->getLogById($logId);
        if (! $log) {
            abort(404);
        }

        $html = template('_components/utilities/DeprecationErrors/traces_modal', [
            'log' => $log,
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    public function destroy(int $logId): Response
    {
        $success = $this->deprecator->deleteLogById($logId);
        if (! $success) {
            return $this->asFailure(t('Could not remove deprecation error.'));
        }

        return $this->asSuccess(t('Deprecation error removed.'));
    }

    public function destroyAll(): Response
    {
        $success = $this->deprecator->deleteAllLogs();
        if (! $success) {
            return $this->asFailure(t('Could not remove deprecation errors.'));
        }

        return $this->asSuccess(t('Deprecation errors removed.'));
    }
}
