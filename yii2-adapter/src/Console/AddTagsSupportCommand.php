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

class AddTagsSupportCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:add-tags-support
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Adds support for tags';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $tagsTable = $schema->getRawTableName(LegacyTable::TAGS);
        $tagGroupsTable = $schema->getRawTableName(LegacyTable::TAGGROUPS);

        if (Schema::hasTable($tagsTable)) {
            $this->info("<fg=cyan>$tagsTable</> table already exists.");

            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Creating <fg=cyan>$tagsTable</> table",
            function() use ($tagsTable) {
                Schema::create($tagsTable, function(Blueprint $table) {
                    $table->integer('id');
                    $table->integer('groupId');
                    $table->boolean('deletedWithGroup')->nullable();
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                });
            },
        );

        $this->components->task(
            "Creating <fg=cyan>$tagGroupsTable</> table",
            function() use ($tagGroupsTable) {
                Schema::create($tagGroupsTable, function(Blueprint $table) {
                    $table->integer('id', true);
                    $table->string('name');
                    $table->string('handle');
                    $table->integer('fieldLayoutId')->nullable();
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                    $table->dateTime('dateDeleted')->nullable()->default(null);
                    $table->char('uid', 36)->default('0');
                });
                Schema::createIndex($tagGroupsTable, ['name']);
                Schema::createIndex($tagGroupsTable, ['handle']);
                Schema::createIndex($tagGroupsTable, ['dateDeleted']);
            },
        );

        $this->components->task(
            'Creating foreign keys',
            function() use ($tagsTable, $tagGroupsTable) {
                Schema::table($tagsTable, function(Blueprint $table) use ($tagGroupsTable) {
                    $table->foreign('groupId')->references('id')->on($tagGroupsTable)->cascadeOnDelete();
                    $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete();
                });
                Schema::table($tagGroupsTable, function(Blueprint $table) {
                    $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete();
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
