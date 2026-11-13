<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use craft\db\Migration;
use craft\db\Query;
use craft\test\TestCase;

/**
 * Unit tests for Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class MigrationTest extends TestCase
{
    private const TABLES = [
        '{{%migrationtest_articles}}',
        '{{%migrationtest_articles_old}}',
        '{{%migrationtest_articles_old1}}',
    ];

    public function testArchiveTableIfExists(): void
    {
        $migration = new class() extends Migration {
        };
        $migration->compact = true;

        foreach (['First', 'Second'] as $title) {
            $migration->archiveTableIfExists('{{%migrationtest_articles}}');
            $migration->createTable('{{%migrationtest_articles}}', [
                'id' => $migration->primaryKey(),
                'title' => $migration->string(),
            ]);
            $migration->insert('{{%migrationtest_articles}}', ['title' => $title]);
        }

        $migration->archiveTableIfExists('{{%migrationtest_articles}}');

        self::assertSame('First', (new Query())->select('title')->from('{{%migrationtest_articles_old}}')->scalar());
        self::assertSame('Second', (new Query())->select('title')->from('{{%migrationtest_articles_old1}}')->scalar());
        self::assertFalse(\Craft::$app->getDb()->tableExists('{{%migrationtest_articles}}'));
    }

    protected function _after(): void
    {
        $migration = new class() extends Migration {
        };
        $migration->compact = true;

        foreach (self::TABLES as $table) {
            $migration->dropTableIfExists($table);
        }

        parent::_after();
    }
}
