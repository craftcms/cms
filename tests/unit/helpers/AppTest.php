<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;

use Codeception\Codecept;
use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\services\Entries;
use yii\base\Component;

/**
 * Unit tests for the App Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class AppTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testEditions()
    {
        $this->assertEquals([Craft::Solo, Craft::Pro], App::editions());
    }

    public function testEditionName()
    {
        $this->assertEquals('Solo', App::editionName(Craft::Solo));
        $this->assertEquals('Pro', App::editionName(Craft::Pro));
    }

    public function testIsValidEdition()
    {
        $this->assertTrue(App::isValidEdition('1'));
        $this->assertFalse(App::isValidEdition(null));
        $this->assertFalse(App::isValidEdition(false));
        $this->assertTrue(App::isValidEdition(0));
        $this->assertFalse(App::isValidEdition(4));
        $this->assertTrue(App::isValidEdition(1));
        $this->assertFalse(App::isValidEdition(2));
        $this->assertFalse(App::isValidEdition(3));
    }

    /**
     * @dataProvider versionListData
     *
     * @param $result
     * @param $input
     */
    public function testInputOutput($result, $input)
    {
        $this->assertSame($result, App::normalizeVersion($input));
    }


    public function versionListData()
    {
        return [
            ['version', 'version 21'],
            ['v120.19.2', 'v120.19.2--beta'],
            ['version', 'version'],
            ['2\0\0', '2\0\0'],
            ['2', '2+2+2'],
            ['2', '2-0-0'],
            ['', ''],
            ['\*v^2.0.0(beta)', '\*v^2.0.0(beta)'],

        ];
    }

    public function testPhpConfigValueAsBool()
    {
        $displayErrorsValue = ini_get('display_errors');
        @ini_set('display_errors', 1);
        $this->assertTrue(App::phpConfigValueAsBool('display_errors'));
        @ini_set('display_errors', $displayErrorsValue);

        $timezoneValue = ini_get('date.timezone');
        @ini_set('date.timezone', Craft::$app->getTimeZone() ?: 'Europe/Amsterdam');
        $this->assertFalse(App::phpConfigValueAsBool('date.timezone'));
        @ini_set('date.timezone', $timezoneValue);

        $this->assertFalse(App::phpConfigValueAsBool(''));
        $this->assertFalse(App::phpConfigValueAsBool('This isnt a config value'));
    }

    public function testClassHumanization()
    {
        $this->assertSame('entries', App::humanizeClass(Entries::class));

        $this->assertSame( '', App::humanizeClass(''));
        $this->assertSame('app test', App::humanizeClass(self::class));
        $this->assertSame('std class', App::humanizeClass(\stdClass::class));
        $this->assertSame('iam not a  class!@#$%^&*()1234567890', App::humanizeClass('iam not a CLASS!@#$%^&*()1234567890'));
    }

    public function testMaxPowerCaptain(){
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $craftMemoryLimit = $generalConfig->phpMaxMemoryLimit;
        $desiredMemLimit = $craftMemoryLimit !== '' ? $craftMemoryLimit : '-1';

        App::maxPowerCaptain();

        $this->assertSame($desiredMemLimit, ini_get('memory_limit'));
        $this->assertSame('0', ini_get('max_execution_time'));

        // Make sure if we set it again all is well.
        App::maxPowerCaptain();
        $this->assertSame($desiredMemLimit, ini_get('memory_limit'));
        $this->assertSame('0', ini_get('max_execution_time'));
    }

    public function testLicenseKey()
    {
        $this->assertSame(250, strlen(App::licenseKey()));
    }

    /**
     * @dataProvider configsData
     */
    public function testConfigIndexes($method, $desiredConfig)
    {
        $config = App::$method();

        $this->assertFalse($this->areKeysMissing($config, $desiredConfig));

        $this->assertTrue(class_exists($config['class']));

        // Make sure its a component
        $this->assertTrue(in_array(Component::class, class_parents($config['class'])));
    }

    public function configsData()
    {
        $viewRequirments = Craft::$app->getRequest()->getIsCpRequest() ? ['class', 'registeredAssetBundled', 'registeredJsFiled'] : ['class'];
        return [
            ['assetManagerConfig', ['class', 'basePath', 'baseUrl', 'fileMode', 'dirMode', 'appendTimestamp']],
            ['dbConfig', [ 'class', 'dsn', 'password', 'username',  'charset', 'tablePrefix',  'schemaMap',  'commandMap',  'attributes','enableSchemaCache' ]],
            ['webRequestConfig', [ 'class',  'enableCookieValidation', 'cookieValidationKey', 'enableCsrfValidation', 'enableCsrfCookie', 'csrfParam',  ]],
            ['cacheConfig', [ 'class',  'cachePath', 'fileMode', 'dirMode', 'defaultDuration']],
            ['mailerConfig', [ 'class',  'messageClass', 'from', 'template', 'transport']],
            ['mutexConfig', [ 'class',  'fileMode', 'dirMode']],
            ['logConfig', [ 'class',  'targets']],
            ['sessionConfig', [ 'class',  'flashParam', 'authAccessParam', 'name', 'cookieParams']],
            ['userConfig', [ 'class',  'identityClass', 'enableAutoLogin', 'autoRenewCookie', 'loginUrl', 'authTimeout', 'identityCookie', 'usernameCookie', 'idParam', 'authTimeoutParam', 'absoluteAuthTimeoutParam', 'returnUrlParam']],
            ['viewConfig', $viewRequirments],

        ];
    }

   private function areKeysMissing(array $configArray, array $desiredSchemaArray) : bool
   {
       foreach ($desiredSchemaArray as $desiredSchemaItem) {
           if (!array_key_exists($desiredSchemaItem, $configArray)) {
               return true;
           }
       }

       return false;
   }
}
