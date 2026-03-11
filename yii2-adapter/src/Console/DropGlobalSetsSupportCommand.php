<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use Craft;
use craft\db\Table as LegacyTable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;

final class DropGlobalSetsSupportCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:drop-global-sets-support
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Drops support for global sets';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $globalSetsTable = $schema->getRawTableName(LegacyTable::GLOBALSETS);

        if (!Schema::hasTable($globalSetsTable)) {
            $this->info("<fg=cyan>$globalSetsTable</> table doesn’t exist.");

            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Dropping <fg=cyan>$globalSetsTable</> table",
            function() use ($globalSetsTable) {
                Schema::dropIfExists($globalSetsTable);
            },
        );

        DeprecatedConcepts::resetSupport();
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return function() {
            return $this->getLaravel()->environment() === 'production' || !Cms::config()->allowAdminChanges;
        };
    }
}
