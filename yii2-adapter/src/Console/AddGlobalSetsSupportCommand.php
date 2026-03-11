<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use Craft;
use craft\db\Table as LegacyTable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class AddGlobalSetsSupportCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:add-global-sets-support
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Adds support for global sets';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $globalSetsTable = $schema->getRawTableName(LegacyTable::GLOBALSETS);

        if (Schema::hasTable($globalSetsTable)) {
            $this->info("<fg=cyan>$globalSetsTable</> table already exists.");

            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Creating <fg=cyan>$globalSetsTable</> table",
            function() use ($globalSetsTable) {
                Schema::create($globalSetsTable, function(Blueprint $table) {
                    $table->integer('id', true);
                    $table->string('name');
                    $table->string('handle');
                    $table->integer('fieldLayoutId')->nullable();
                    $table->unsignedSmallInteger('sortOrder')->nullable();
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                    $table->char('uid', 36)->default('0');
                });
                Schema::createIndex($globalSetsTable, ['name']);
                Schema::createIndex($globalSetsTable, ['handle']);
                Schema::createIndex($globalSetsTable, ['fieldLayoutId']);
                Schema::createIndex($globalSetsTable, ['sortOrder']);
            },
        );

        $this->components->task(
            'Creating foreign keys',
            function() use ($globalSetsTable) {
                Schema::table($globalSetsTable, function(Blueprint $table) {
                    $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete();
                    $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete();
                });
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
