<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateMigrationTableCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:migrate:migration-table
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Migrates the migration table to the new format';

    public function handle(Migrator $migrator): void
    {
        if (Schema::hasColumn(Table::MIGRATIONS, 'migration')) {
            $this->info('Migration table already migrated.');

            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $history = DB::table(Table::MIGRATIONS)->get();

        $this->components->task(
            'Dropping old migration table',
            fn() => Schema::dropIfExists(Table::MIGRATIONS),
        );

        $this->components->task(
            'Creating new migration table',
            fn() => $migrator->getRepository()->createRepository(),
        );

        $this->components->task(
            'Inserting old migration data',
            function() use ($history) {
                foreach ($history as $migration) {
                    DB::table(Table::MIGRATIONS)->insert([
                        'migration' => $migration->name,
                        'track' => $migration->track === 'content' ? null : $migration->track,
                        'batch' => 1,
                    ]);
                }
            }
        );
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return function() {
            return $this->getLaravel()->environment() === 'production' || !Cms::config()->allowAdminChanges;
        };
    }
}
