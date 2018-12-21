<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craft\test;


use Codeception\Module\Yii2;
use Codeception\TestInterface;

/**
 * Craft module for codeception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class Craft extends Yii2
{
    public function _before(TestInterface $test)
    {
        parent::_before($test);

        /**
         * TODO:
         * There is a potential 'bug'/hampering feature with the Yii2 Codeception module.
         * DB connections initialized through the configFile param (see https://codeception.com/docs/modules/Yii2)
         * Are not captured by the Yii2Connector\ConnectionWatcher and Yii2Connector\TransactionForcer i.e. all DB interacitons done through
         * Craft::$app->getDb() are not stored and roll'd back in transacitons.
         *
         * This is probably because the starting of the app (triggered by $this->client->startApp()) is done BEFORE the
         * DB event listeners are registered. Moving the order of these listeners to the top of the _before function means the conneciton
         * is registered.
         *
         * What i need to investigate is whether iam doing something wrong in the src/tests/_craft/config/test.php or this is PR 'worthy'
         * For now: Remounting the DB object using Craft::$app->set() after the event listeners are called works perfectly fine.
         */
        $db = \Craft::createObject(
            \craft\helpers\App::dbConfig(new \craft\config\DbConfig([
                'database' => getenv('TEST_DB_NAME'),
                'driver' => getenv('TEST_DB_DRIVER'),
                'user' => getenv('TEST_DB_USER'),
                'password' =>getenv('TEST_DB_PASS'),
                'tablePrefix' => getenv('TEST_DB_TABLE_PREFIX'),
                'port' => '3306',
            ])));

        \Craft::$app->set('db', $db);
    }
}