<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\cache;

use Craft;
use craft\cache\DbCache;
use craft\console\controllers\SetupController;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\test\TestCase;
use PDO;
use yii\console\ExitCode;

/**
 * Unit tests for DbCache
 */
class DbCacheTest extends TestCase
{
    /**
     * @see https://github.com/craftcms/cms/issues/19877
     * @dataProvider enableSchemaCacheProvider
     */
    public function testSetValueWithinQueryCacheScope(bool $enableSchemaCache): void
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
            'tablePrefix' => 'dbcache_' . bin2hex(random_bytes(4)) . '_',
        ]);
        // Keep the cache table independent of Codeception's shared PDO transaction.
        $db->pdo = new PDO($db->dsn, $db->username, $db->password, $db->attributes);

        // Track how deeply setValue() gets re-entered, bailing out rather than exhausting memory
        $cache = new class(['db' => $db, 'cacheTable' => Table::CACHE]) extends DbCache {
            public int $depth = 0;
            public int $maxDepth = 0;

            protected function setValue($key, $value, $duration): bool
            {
                $this->maxDepth = max($this->maxDepth, ++$this->depth);
                try {
                    return $this->depth > 5 ? false : parent::setValue($key, $value, $duration);
                } finally {
                    $this->depth--;
                }
            }
        };
        $db->schemaCache = $cache;
        $db->queryCache = $cache;
        Craft::$app->set('db', $db);

        try {
            $controller = new SetupController('setup', Craft::$app, ['interactive' => false]);
            self::assertSame(ExitCode::OK, $controller->actionDbCacheTable());

            // Start with cold schema metadata and an empty cache
            $db->enableSchemaCache = $enableSchemaCache;
            $db->getSchema()->refresh();
            $cache->flush();

            $count = $db->cache(fn() => (new Query())->from(Table::CACHE)->count(db: $db), 300);

            self::assertSame(0, (int)$count);
            self::assertSame(1, $cache->maxDepth);
        } finally {
            $db->enableSchemaCache = false;
            try {
                $db->createCommand()->dropTableIfExists(Table::CACHE)->execute();
            } finally {
                Craft::$app->set('db', $originalDb);
                $db->close();
            }
        }
    }

    public static function enableSchemaCacheProvider(): array
    {
        return [
            'schema cache enabled' => [true],
            'schema cache disabled' => [false],
        ];
    }
}
