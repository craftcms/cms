<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Codeception\Test\Unit;
use Craft;
use craft\test\TestCase;
use yii\db\Schema;

/**
 * Unit tests for the QueryBuilder class Craft implements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.4
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @dataProvider createTableOptionsDataProvider
     * @param bool $contains
     * @param string $needle
     * @param string|null $options
     * @return void
     */
    public function testCreateTableOptions(bool $contains, string $needle, ?string $options = null): void
    {
        $db = Craft::$app->getDb();

        if (!$db->getIsMysql()) {
            $this->markTestSkipped('MySQL only');
        }

        $dbConfig = Craft::$app->getConfig()->getDb();
        $charset = $dbConfig->getCharset();
        $collation = $dbConfig->collation;
        $dbConfig->charset = 'utf8';
        $dbConfig->collation = null;

        $sql = $db->getQueryBuilder()->createTable('foo', ['id' => Schema::TYPE_PK], $options);

        $dbConfig->charset = $charset;
        $dbConfig->collation = $collation;

        if ($contains) {
            self::assertStringContainsString($needle, $sql);
        } else {
            self::assertStringNotContainsString($needle, $sql);
        }
    }

    /**
     * @return array
     */
    public static function createTableOptionsDataProvider(): array
    {
        return [
            [true, 'ENGINE = InnoDb'],
            [true, 'DEFAULT CHARACTER SET = utf8'],
            [false, 'DEFAULT CHARACTER SET = utf8', 'character    set   foo'],
            [false, 'DEFAULT CHARACTER SET = utf8', 'CHARACTER SET = foo'],
            [true, 'DEFAULT CHARACTER SET = utf8', 'CHARACTER SETS = foo'],
            [true, 'CHARACTER SET = foo', 'CHARACTER SET = foo'],
            [true, 'COLLATE = utf8_unicode_ci', 'COLLATE = utf8_unicode_ci'],
        ];
    }
}
