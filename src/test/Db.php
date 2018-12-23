<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craft\test;


use craft\config\DbConfig;
use craft\helpers\App;

/**
 * Class DB.
 *
 *
 * TODO: Now that we have a Craft module that extends the Yii2 module. Shouldnt we just put this code there
 * and initialize in that module (the _initialize() method). Then we can ignore the DB module in the codeception.yaml and start working
 * on integrating plugins and modules?
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class Db extends \Codeception\Module\Db
{
    /**
     * Load dump.
     *
     * When using the craft module(in its current state) _loadDump only loads the install migration and associated tables
     * Fk's an indexes. Aswell as setting up a base user and site.
     *
     * TODO: Plugin migrations, additional migrations e.t.c.
     *
     * @param null $databaseKey
     * @param null $databaseConfig
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function _loadDump($databaseKey = null, $databaseConfig = null)
    {
        TestSetup::warmCraft();

        ob_start();
        $conn = \Craft::createObject(App::dbConfig(new DbConfig([
            'dsn' => $databaseConfig['dsn'],
            'password' => $databaseConfig['password'],
            'user' => $databaseConfig['user'],
            'tablePrefix' => 'craft'
        ])));

        \Craft::$app->set('db', $conn);

        $testSetup = new TestSetup($conn);
        $testSetup->clenseDb();
        $testSetup->setupDb();

        ob_clean();

        TestSetup::tearDownCraft();
    }
}