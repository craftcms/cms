<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use Craft;
use craft\errors\MigrationException;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\Request;

class MigrationsController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(Utilities\Migrations::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request)
    {
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        $migrator = $craft->getContentMigrator();

        try {
            $migrator->up();
            Flash::success(Craft::t('app', 'Applied new migrations successfully.'));
        } catch (MigrationException) {
            Flash::fail(Craft::t('app', 'Couldn’t apply new migrations.'));
        }

        return cp_redirect('utilities/migrations');
    }
}
