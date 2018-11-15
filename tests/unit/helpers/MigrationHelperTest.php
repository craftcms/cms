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

    private function createForeignKey($source, $target)
    {
        MigrationHelper::restoreForeignKey()
    }
}