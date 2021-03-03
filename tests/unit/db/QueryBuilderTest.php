<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Codeception\Test\Unit;
use Craft;
use craft\db\ActiveRecord;
use craft\helpers\StringHelper;
use craft\records\Session;
use craft\records\Volume;
use craft\test\mockclasses\serializable\Serializable;
use craft\volumes\Local;
use DateTime;
use DateTimeZone;
use Exception;
use stdClass;
use UnitTester;
use yii\db\Schema;

/**
 * Unit tests for the QueryBuilder class Craft implements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.4
 */
class QueryBuilderTest extends Unit
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

        $sql = $db->getQueryBuilder()->createTable('foo', ['id' => Schema::TYPE_PK], $options);

        if ($contains) {
            $this->assertStringContainsString($needle, $sql);
        } else {
            $this->assertStringNotContainsString($needle, $sql);
        }
    }

    /**
     * @return array
     */
    public function createTableOptionsDataProvider(): array
    {
        return [
            [true, 'ENGINE = InnoDb'],
            [true, 'DEFAULT CHARACTER SET = utf8'],
            [false, 'DEFAULT CHARACTER SET = utf8', 'character    set   foo'],
            [false, 'DEFAULT CHARACTER SET = utf8', 'CHARACTER SET = foo'],
            [true, 'DEFAULT CHARACTER SET = utf8', 'CHARACTER SETS = foo'],
            [true, 'CHARACTER SET = foo', 'CHARACTER SET = foo'],
            [false, 'COLLATE'],
            [true, 'COLLATE = utf8_unicode_ci', 'COLLATE = utf8_unicode_ci'],
        ];
    }
}