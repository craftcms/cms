<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console\controllers;

use Craft;
use craft\cache\DbCache;
use craft\console\controllers\SetupController;
use craft\db\Connection;
use craft\db\Table;
use craft\test\console\ConsoleTest;
use PDO;
use RuntimeException;
use yii\console\ExitCode;

/**
 * Tests setup with a database-backed schema cache.
 */
class SetupControllerTest extends ConsoleTest
{
    /**
     * @dataProvider setupOrderProvider
     * @param array $actions
     * @return void
     */
    public function testDatabaseCacheBootstrap(array $actions): void
    {
        $originalDb = Craft::$app->getDb();
        $db = new Connection([
            'dsn' => $originalDb->dsn,
            'username' => $originalDb->username,
            'password' => $originalDb->password,
            'charset' => $originalDb->charset,
            'attributes' => $originalDb->attributes,
            'schemaMap' => $originalDb->schemaMap,
            'commandMap' => $originalDb->commandMap,
            'tablePrefix' => 'bootstrap_' . bin2hex(random_bytes(4)) . '_',
            'enableSchemaCache' => true,
        ]);
        // Keep setup migrations independent of Codeception's shared PDO transaction.
        $db->pdo = new PDO($db->dsn, $db->username, $db->password, $db->attributes ?? []);
        $cache = new DbCache(['db' => $db, 'cacheTable' => Table::CACHE]);
        $db->schemaCache = $cache;
        Craft::$app->set('db', $db);
        $controller = new SetupController('setup', Craft::$app, ['interactive' => false]);

        try {
            foreach ($actions as $action) {
                self::assertSame(ExitCode::OK, $controller->$action());
                self::assertTrue($db->enableSchemaCache);
            }

            // Exercise each write with cold schema metadata and an empty cache.
            foreach (['add', 'set'] as $method) {
                $db->enableSchemaCache = false;
                $db->getSchema()->refresh();
                $cache->flush();
                $db->enableSchemaCache = true;

                self::assertTrue($cache->$method('bootstrap', 'value'));
                self::assertSame('value', $cache->get('bootstrap'));
                self::assertTrue($db->enableSchemaCache);
            }

            self::assertNotNull($db->getTableSchema(Table::PHPSESSIONS));

            // Both commands must remain safe to run again.
            foreach ($actions as $action) {
                self::assertSame(ExitCode::OK, $controller->$action());
                self::assertTrue($db->enableSchemaCache);
            }
        } finally {
            $db->enableSchemaCache = false;
            try {
                $db->createCommand()->dropTableIfExists(Table::PHPSESSIONS)->execute();
                $db->createCommand()->dropTableIfExists(Table::CACHE)->execute();
            } finally {
                Craft::$app->set('db', $originalDb);
                $db->close();
            }
        }
    }

    /**
     * @return array
     */
    public static function setupOrderProvider(): array
    {
        return [
            'sessions first' => [['actionPhpSessionTable', 'actionDbCacheTable']],
            'cache first' => [['actionDbCacheTable', 'actionPhpSessionTable']],
        ];
    }

    /**
     * @dataProvider setupFailureProvider
     * @param string $action
     * @param bool $enableSchemaCache
     * @return void
     */
    public function testRestoresSchemaCachingAfterFailure(string $action, bool $enableSchemaCache): void
    {
        $originalDb = Craft::$app->getDb();
        $db = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['tableExists'])
            ->getMock();
        $db->enableSchemaCache = $enableSchemaCache;
        $db->expects(self::once())->method('tableExists')->willThrowException(new RuntimeException('Setup failed.'));
        Craft::$app->set('db', $db);

        try {
            $controller = new SetupController('setup', Craft::$app);
            $this->expectException(RuntimeException::class);
            $controller->$action();
        } finally {
            Craft::$app->set('db', $originalDb);
            self::assertSame($enableSchemaCache, $db->enableSchemaCache);
        }
    }

    /**
     * @return array
     */
    public static function setupFailureProvider(): array
    {
        return [
            ['actionPhpSessionTable', true],
            ['actionPhpSessionTable', false],
            ['actionDbCacheTable', true],
            ['actionDbCacheTable', false],
        ];
    }
}
