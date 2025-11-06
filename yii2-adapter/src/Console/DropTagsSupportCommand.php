<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use Craft;
use craft\db\Table as LegacyTable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;

final class DropTagsSupportCommand extends Command
{
    use CraftCommand;
    use ConfirmableTrait;

    protected $signature = 'craft:drop-tags-support
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Drops support for tags';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $tagsTable = $schema->getRawTableName(LegacyTable::TAGS);
        $tagGroupsTable = $schema->getRawTableName(LegacyTable::TAGGROUPS);

        if (!Schema::hasTable($tagsTable)) {
            $this->info("<fg=cyan>$tagsTable</> table doesn’t exist.");
            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Dropping <fg=cyan>$tagsTable</> table",
            function() use ($tagsTable) {
                Schema::dropIfExists($tagsTable);
            },
        );

        $this->components->task(
            "Dropping <fg=cyan>$tagGroupsTable</> table",
            function() use ($tagGroupsTable) {
                Schema::dropIfExists($tagGroupsTable);
            },
        );

        Yii2ServiceProvider::resetSupport();
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return function() {
            return $this->getLaravel()->environment() === 'production' || !Cms::config()->allowAdminChanges;
        };
    }
}
