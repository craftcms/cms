<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use Craft;
use craft\db\Table as LegacyTable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;

final class DropCategoriesTableCommand extends Command
{
    use CraftCommand;
    use ConfirmableTrait;

    protected $signature = 'craft:drop-categories-table
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Drops the database table for storing categories';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $categoriesTable = $schema->getRawTableName(LegacyTable::CATEGORIES);
        $categoryGroupsTable = $schema->getRawTableName(LegacyTable::CATEGORYGROUPS);
        $categoryGroupsSitesTable = $schema->getRawTableName(LegacyTable::CATEGORYGROUPS_SITES);

        if (!Schema::hasTable($categoriesTable)) {
            $this->info("<fg=cyan>$categoriesTable</> table doesn’t exist.");
            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Dropping <fg=cyan>$categoryGroupsSitesTable</> table",
            function() use ($categoryGroupsSitesTable) {
                Schema::dropIfExists($categoryGroupsSitesTable);
            },
        );

        $this->components->task(
            "Dropping <fg=cyan>$categoryGroupsTable</> table",
            function() use ($categoryGroupsTable) {
                Schema::dropIfExists($categoryGroupsTable);
            },
        );

        $this->components->task(
            "Dropping <fg=cyan>$categoriesTable</> table",
            function() use ($categoriesTable) {
                Schema::dropIfExists($categoriesTable);
            },
        );
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return function() {
            return $this->getLaravel()->environment() === 'production' || !Cms::config()->allowAdminChanges;
        };
    }
}
