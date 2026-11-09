<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\Commands\ApplyCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\CompiledViews;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Override;
use Throwable;

use function CraftCms\Cms\t;
use function Laravel\Prompts\confirm;

/**
 * @since 6.0.0
 */
class UpCommand extends Command implements Isolatable
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:up {--noBackup : Skip backing up the database.}';

    #[Override]
    protected $description = 'Runs pending migrations and applies pending project config changes.';

    public function handle(Updates $updates, License $license, ProjectConfig $projectConfig): int
    {
        $this->showLicensingIssues($updates, $license);

        try {
            $pendingChanges = $projectConfig->areChangesPending(force: true);
            $writeYamlAutomatically = $projectConfig->writeYamlAutomatically;

            // Craft + plugin migrations
            $res = $this->call(MigrateCommand::class, [
                '--force' => true,
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
                $res = $this->call(ApplyCommand::class);
                if ($res !== self::SUCCESS) {
                    return $res;
                }

                $projectConfig->flush();
                $projectConfig->reset();
            }

            // Content migration
            $res = $this->call(MigrateCommand::class, [
                '--force' => true,
                '--track' => 'content',
            ]);

            if ($res !== self::SUCCESS) {
                return $res;
            }

            $this->call('craft:clear-caches', [
                'keys' => ['compiled-templates'],
            ]);

            $this->clearCompiledViews();
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

    /**
     * Clears compiled Blade views, so updated control panel views aren't shadowed by stale compiled copies.
     *
     * The update has already been applied by this point, so a failure is reported rather than failing the command.
     */
    private function clearCompiledViews(): void
    {
        if (CompiledViews::path() === null) {
            return;
        }

        try {
            $this->components->task(t('Clearing:').' '.t('Compiled views'), CompiledViews::clear(...));
        } catch (Throwable $e) {
            report($e);
            $this->components->warn('Could not clear compiled views: '.$e->getMessage().' Run `php artisan view:clear` manually.');
        }
    }

    private function showLicensingIssues(Updates $updates, License $license): bool
    {
        $this->components->task('Updating license info', function () use ($updates) {
            $updates->getUpdates(refresh: true);
        });

        $issues = $license->issues([LicenseKeyStatus::Astray->value]);

        if (empty($issues)) {
            return true;
        }

        $this->components->error('The following licensing issues were detected:');
        $this->components->bulletList(array_map(fn (array $issue) => $issues[1], $issues));

        return confirm('Continue anyway?');
    }
}
