<?php

declare(strict_types=1);

use craft\db\Migration;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Yii2Adapter\Database\MigrationWrapper;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

afterEach(function() {
    foreach (['legacymigration_articles', 'legacymigration_articles_old', 'legacymigration_articles_old1'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('runs legacy migrations that add foreign keys and drop tables', function() {
    $migrator = app(Migrator::class);

    $migrator->runMigration((new MigrationWrapper(LegacyForeignKeyMigration::class))->silent(), 'up');

    expect(Schema::getForeignKeys('legacymigration_articles'))
        ->toHaveCount(1)
        ->sequence(fn($foreignKey) => $foreignKey->toMatchArray([
            'columns' => ['entryId'],
            'foreign_table' => 'entries',
            'foreign_columns' => ['id'],
            'on_delete' => 'cascade',
        ]))
        ->and(Schema::getIndexListing('legacymigration_articles'))
        ->toContain('idx_legacymigration_entryid');

    $migrator->runMigration((new MigrationWrapper(LegacyForeignKeyMigration::class))->silent(), 'down');

    expect(Schema::hasTable('legacymigration_articles'))->toBeFalse();
});

it('archives tables with an _old suffix', function() {
    $migration = new class() extends Migration {
    };

    foreach (['First', 'Second'] as $title) {
        $migration->archiveTableIfExists('{{%legacymigration_articles}}');
        $migration->createTable('{{%legacymigration_articles}}', [
            'id' => $migration->primaryKey(),
            'title' => $migration->string(),
        ]);
        $migration->insert('{{%legacymigration_articles}}', ['title' => $title]);
    }

    $migration->archiveTableIfExists('{{%legacymigration_articles}}');

    expect(DB::table('legacymigration_articles_old')->value('title'))->toBe('First')
        ->and(DB::table('legacymigration_articles_old1')->value('title'))->toBe('Second')
        ->and(Schema::hasTable('legacymigration_articles'))->toBeFalse();
});

it('rethrows exceptions from legacy migrations', function() {
    app(Migrator::class)->runMigration((new MigrationWrapper(LegacyFailingMigration::class))->silent(), 'up');
})->throws(RuntimeException::class, 'Legacy migration failed');

class LegacyForeignKeyMigration extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%legacymigration_articles}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex('idx_legacymigration_entryid', '{{%legacymigration_articles}}', 'entryId', true);
        $this->addForeignKey(null, '{{%legacymigration_articles}}', ['entryId'], Table::ENTRIES, ['id'], 'CASCADE');

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%legacymigration_articles}}');

        return true;
    }
}

class LegacyFailingMigration extends Migration
{
    public function safeUp(): bool
    {
        throw new RuntimeException('Legacy migration failed');
    }
}
