<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Throwable;

final class UpCommand extends Command implements Isolatable
{
    use CraftCommand;

    protected $signature = 'craft:up {--noBackup : Skip backing up the database.}';

    protected $description = 'Runs pending migrations and applies pending project config changes.';

    public function handle(Updates $updates, ProjectConfig $projectConfig): int
    {
        try {
            $pendingChanges = $projectConfig->areChangesPending(force: true);
            $writeYamlAutomatically = $projectConfig->writeYamlAutomatically;

            // Craft + plugin migrations
            $res = $this->call(MigrateCommand::class, [
                '--noContent' => true,
                '--noBackup' => $this->option('noBackup'),
            ]);

            if ($res !== self::SUCCESS) {
                $this->components->warn('Aborting remaining tasks.');

                return $res;
            }

            // Save and reset the project config
            $projectConfig->saveModifiedConfigData();
            $projectConfig->reset();

            // Project Config
            if ($pendingChanges) {
                /** @todo Laravel command */
                $res = $this->call('craft:project-config/apply');
                if ($res !== self::SUCCESS) {
                    return $res;
                }

                $projectConfig->flush();
                $projectConfig->reset();
            }

            // Content migration
            $res = $this->call(MigrateCommand::class, [
                '--track' => 'content',
            ]);

            if ($res !== self::SUCCESS) {
                return $res;
            }

            $this->components->task('Updating license info', function () use ($updates) {
                $updates->getUpdates(refresh: true);
            });
        } catch (Throwable $e) {
            if (! $e instanceof OperationAbortedException) {
                throw $e;
            }

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($writeYamlAutomatically && ! $projectConfig->readOnly) {
            $projectConfig->writeYamlFiles(force: true);
        }

        return self::SUCCESS;
    }
}
