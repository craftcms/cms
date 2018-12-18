<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\MigrationHelper;


/**
 * Class MigrationHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class MigrationHelperTest extends Unit
{
    public function _before()
    {
        \Craft::$app->getDb()->createCommand()
            ->createTable('craftunit_test1', []);

        \Craft::$app->getDb()->createCommand()
            ->createTable('craftunit_test2', []);

        \Craft::$app->getDb()->createCommand()
            ->createTable('craftunit_test3', []);
    }

    public function _after()
    {
        MigrationHelper::dropTable('craftunit_test1');
        MigrationHelper::dropTable('craftunit_test2');
        MigrationHelper::dropTable('craftunit_test3');

    }


    private function setupForeignKeys()
    {
        $this->createForeignKey('craftunit_test1', 'craftunit_test2', 'test', 'test');
        $this->createForeignKey('craftunit_test2', 'craftunit_test3', 'test', 'test');
        $this->createForeignKey('craftunit_test3', 'craftunit_test3', 'test', 'test');
    }

    private function createForeignKey($sourceTable, $targetTable, $sourceCol, $targetCol, $update = 'NO ACTION', $delete = 'CASCADE')
    {
        MigrationHelper::restoreForeignKey($sourceTable, $sourceCol, $targetTable, $targetCol, $update, $delete);
    }
}