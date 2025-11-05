<?php

namespace CraftCms\Yii2Adapter\Console;

use Closure;
use Craft;
use craft\db\Table as LegacyTable;
use craft\models\CategoryGroup;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class AddCategoriesSupportCommand extends Command
{
    use CraftCommand;
    use ConfirmableTrait;

    protected $signature = 'craft:add-categories-support
        {--force : Force the operation to run when in production or when allowAdminChanges is disabled.}
    ';

    protected $description = 'Adds support for categories';

    public function handle(Migrator $migrator): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $categoriesTable = $schema->getRawTableName(LegacyTable::CATEGORIES);
        $categoryGroupsTable = $schema->getRawTableName(LegacyTable::CATEGORYGROUPS);
        $categoryGroupsSitesTable = $schema->getRawTableName(LegacyTable::CATEGORYGROUPS_SITES);

        if (Schema::hasTable($categoriesTable)) {
            $this->info("<fg=cyan>$categoriesTable</> table already exists.");
            return;
        }

        if (!$this->confirmToProceed('Application In Production or allowAdminChanges is disabled.')) {
            return;
        }

        $this->components->task(
            "Creating <fg=cyan>$categoriesTable</> table",
            function() use ($categoriesTable) {
                Schema::create($categoriesTable, function(Blueprint $table) {
                    $table->integer('id');
                    $table->integer('groupId');
                    $table->integer('parentId')->nullable();
                    $table->boolean('deletedWithGroup')->nullable();
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                    $table->primary('id');
                });
            },
        );

        $this->components->task(
            "Creating <fg=cyan>$categoryGroupsTable</> table",
            function() use ($categoryGroupsTable) {
                Schema::create($categoryGroupsTable, function(Blueprint $table) {
                    $table->integer('id', true);
                    $table->integer('structureId');
                    $table->integer('fieldLayoutId')->nullable();
                    $table->string('name');
                    $table->string('handle');
                    $table->enum('defaultPlacement', [CategoryGroup::DEFAULT_PLACEMENT_BEGINNING, CategoryGroup::DEFAULT_PLACEMENT_END])->default('end');
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                    $table->dateTime('dateDeleted')->nullable()->default(null);
                    $table->char('uid', 36)->default('0');
                });
                Schema::createIndex($categoryGroupsTable, ['name']);
                Schema::createIndex($categoryGroupsTable, ['handle']);
                Schema::createIndex($categoryGroupsTable, ['structureId']);
                Schema::createIndex($categoryGroupsTable, ['fieldLayoutId']);
                Schema::createIndex($categoryGroupsTable, ['dateDeleted']);
            },
        );

        $this->components->task(
            "Creating <fg=cyan>$categoryGroupsSitesTable</> table",
            function() use ($categoryGroupsSitesTable) {
                Schema::create($categoryGroupsSitesTable, function(Blueprint $table) {
                    $table->integer('id', true);
                    $table->integer('groupId');
                    $table->integer('siteId');
                    $table->boolean('hasUrls')->default(true);
                    $table->text('uriFormat')->nullable();
                    $table->string('template', 500)->nullable();
                    $table->dateTime('dateCreated');
                    $table->dateTime('dateUpdated');
                    $table->char('uid', 36)->default('0');
                });
                Schema::createIndex($categoryGroupsSitesTable, ['groupId', 'siteId'], unique: true);
                Schema::createIndex($categoryGroupsSitesTable, ['siteId']);
            },
        );

        $this->components->task(
            'Creating foreign keys',
            function() use ($categoriesTable, $categoryGroupsTable, $categoryGroupsSitesTable) {
                Schema::table($categoriesTable, function(Blueprint $table) use ($categoriesTable, $categoryGroupsTable) {
                    $table->foreign('groupId')->references('id')->on($categoryGroupsTable)->cascadeOnDelete();
                    $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete();
                    $table->foreign('parentId')->references('id')->on($categoriesTable)->nullOnDelete();
                });
                Schema::table($categoryGroupsTable, function(Blueprint $table) {
                    $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete();
                    $table->foreign('structureId')->references('id')->on(Table::STRUCTURES)->cascadeOnDelete();
                });
                Schema::table($categoryGroupsSitesTable, function(Blueprint $table) use ($categoryGroupsTable) {
                    $table->foreign('groupId')->references('id')->on($categoryGroupsTable)->cascadeOnDelete();
                    $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate();
                });
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
