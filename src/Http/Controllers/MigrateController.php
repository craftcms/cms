<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Foundation\MaintenanceModeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class MigrateController
{
    /**
     * Creates a DB backup (if configured to do so), runs any pending Craft,
     * plugin, & content migrations, and syncs `project.yaml` changes in one go.
     *
     * This action can be used as a post-deploy webhook with site deployment
     * services (like [DeployBot](https://deploybot.com/) or [DeployPlace](https://deployplace.com/)) to minimize site
     * downtime after a deployment.
     */
    public function __invoke(Request $request, GeneralConfig $generalConfig, Updates $updates, ProjectConfig $projectConfig, MaintenanceModeManager $maintenance)
    {
        $handles = $updates->pendingMigrationHandles(true);
        $runMigrations = ! empty($handles);

        if ($applyProjectConfigChanges = $request->boolean('applyProjectConfigChanges')) {
            $applyProjectConfigChanges = $projectConfig->areChangesPending(force: true);
        }

        if (! $runMigrations && ! $applyProjectConfigChanges) {
            // That was easy
            return response()->noContent();
        }

        // Bail if Craft is already in maintenance mode
        if (app()->isDownForMaintenance()) {
            throw new ServiceUnavailableHttpException('Craft is already being updated.');
        }

        // Enable maintenance mode
        $maintenance->activate([]);

        // Backup the DB?
        if ($generalConfig->getBackupOnUpdate()) {
            try {
                $backupPath = Craft::$app->getDb()->backup();
            } catch (Throwable $e) {
                $maintenance->deactivate();
                throw new HttpException(500, 'Error backing up the database.', $e);
            }
        }

        DB::beginTransaction();

        try {
            // Run the migrations?
            if ($runMigrations) {
                $updates->runMigrations($handles);
            }

            // Sync project.yaml?
            if ($applyProjectConfigChanges) {
                try {
                    $projectConfig->applyExternalChanges();
                } catch (BusyResourceException|StaleResourceException $e) {
                    report($e);
                    Log::warning("Couldn’t apply project config YAML changes: {$e->getMessage()}", [__METHOD__]);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            // MySQL may have implicitly committed the transaction
            $restored = DB::isPgsql();

            // Do we have a backup?
            if (! $restored && ! empty($backupPath)) {
                // Attempt a restore
                try {
                    Craft::$app->getDb()->restore($backupPath);
                    $restored = true;
                } catch (Throwable $restoreException) {
                    // Just log it
                    report($restoreException);
                }
            }

            $error = 'An error occurred running new migrations.';
            $error .= match (true) {
                $restored => ' The database has been restored to its previous state.',
                isset($restoreException) => ' The database could not be restored due to a separate error: '.$restoreException->getMessage(),
                default => ' The database has not been restored.',
            };

            $maintenance->deactivate();

            throw new HttpException(500, $error, $e);
        }

        $maintenance->deactivate();

        return response()->noContent();
    }
}
