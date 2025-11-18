<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\Migrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

use function CraftCms\Cms\cp_redirect;
use function CraftCms\Cms\t;

final readonly class MigrationsController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(Migrations::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request, Migrator $migrator)
    {
        try {
            $migrator->track('content')->run();
            Flash::success(t('Applied new migrations successfully.'));
        } catch (Throwable $e) {
            Log::error($e);

            Flash::fail(t('Couldn’t apply new migrations.'));
        }

        return cp_redirect('utilities/migrations');
    }
}
