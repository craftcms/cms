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
use crafttests\fixtures\EntryWithFieldsFixture;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Unit tests for App
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FieldLayoutTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function _fixtures(): array
    {
        return [
            'entry-with-fields' => [
                'class' => EntryWithFieldsFixture::class
            ]
        ];
    }

    /**
     * @throws NotSupportedException
     */
    public function testFieldLayoutMatrix()
    {
        $tableNames = Craft::$app->getDb()->getSchema()->tableNames;
        $matrixTableName = Craft::$app->getDb()->tablePrefix . 'matrixcontent_matrixfirst';

        $this->assertContains($matrixTableName, $tableNames);

        $matrixRows = (new Query())
            ->select('*')->from($matrixTableName)->all();

        $this->assertCount(2, $matrixRows);

        foreach ($matrixRows as $row) {
            $this->assertSame('Some text', $row['field_aBlock_firstSubfield']);
        }
    }
}
