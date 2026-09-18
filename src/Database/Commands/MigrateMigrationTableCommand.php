<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Table;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;
use Override;

class MigrateMigrationTableCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:migrate:migration-table
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    #[Override]
    protected $description = 'Migrates the migration table to the new format';

    public function handle(LaravelMigrations $migrations): int
    {
        if (Schema::hasColumn(Table::MIGRATIONS, 'migration')) {
            $this->components->info('Migration table already migrated.');

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return self::FAILURE;
        }

        $this->components->task(
            'Migrating the migration table',
            fn () => $migrations->ensureMigrationTableFormat(),
        );

        return self::SUCCESS;
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return fn () => $this->getLaravel()->environment('production') || ! Cms::config()->allowAdminChanges;
    }
}
