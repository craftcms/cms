<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use Craft;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\Migrations;
use Illuminate\Http\Request;
use Throwable;

/** @since 6.0.0 */
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
            Flash::success(Craft::t('app', 'Applied new migrations successfully.'));
        } catch (Throwable) {
            Flash::fail(Craft::t('app', 'Couldn’t apply new migrations.'));
        }

        return cp_redirect('utilities/migrations');
    }
}
