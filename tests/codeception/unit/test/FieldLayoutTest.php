<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithFieldsFixture;
use yii\base\NotSupportedException;

/**
 * Unit tests for App
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FieldLayoutTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entry-with-fields' => [
                'class' => EntryWithFieldsFixture::class,
            ],
        ];
    }

    /**
     * @throws NotSupportedException
     */
    public function testFieldLayoutMatrix(): void
    {
        $tableNames = Craft::$app->getDb()->getSchema()->tableNames;
        $matrixTableName = Craft::$app->getDb()->tablePrefix . 'matrixcontent_matrixfirst';

        self::assertContains($matrixTableName, $tableNames);

        $matrixRows = (new Query())
            ->select('*')->from($matrixTableName)->all();

        self::assertCount(2, $matrixRows);

        foreach ($matrixRows as $row) {
            self::assertSame('Some text', $row['field_aBlock_firstSubfield_aaaaaaaa']);
        }
    }
}
