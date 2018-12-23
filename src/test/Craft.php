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
    /*
     * TODO: Place the DB setup code in here rather than the DB module.
     */
    public function _initialize()
    {
        parent::_initialize();
    }

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
         * What i need to investigate is whether iam doing something wrong in the src/tests/_craft/config/test.php or if this is PR 'worthy'
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


    public function _after(TestInterface $test)
    {
        // https://github.com/yiisoft/yii2/issues/11633 || The (possibly) MyISAM {{%searchindex}} table doesnt support transactions.
        // So we manually delete any rows in there except if the element id is 1 (The user added when creating the DB)
        parent::_after($test);

        \Craft::$app->getDb()->createCommand()
            ->delete('{{%searchindex}}', 'elementId != 1')
            ->execute();
    }
}