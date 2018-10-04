<?php
namespace craftunit\helpers;

use Codeception\Codecept;
use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\services\Entries;

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

    public function testVersionNormalization()
    {
        $this->assertSame('2.0.0', App::normalizeVersion('2.0.0--beta'));
        $this->assertSame('v120.19.2', App::normalizeVersion('v120.19.2--beta'));
        $this->assertSame('version', App::normalizeVersion('version'));
        $this->assertSame('version', App::normalizeVersion('version 21'));
        $this->assertSame('2', App::normalizeVersion('2-0-0'));
        $this->assertSame('2', App::normalizeVersion('2+0+0'));
        $this->assertSame('2\0\0', App::normalizeVersion('2\0\0'));
        $this->assertSame('^200', App::normalizeVersion('^200'));
        $this->assertSame('v^2\0.0', App::normalizeVersion('v^2\0.0'));
        $this->assertSame('v^2|0.0', App::normalizeVersion('v^2|0.0'));
        $this->assertSame('~v^2.0.0', App::normalizeVersion('~v^2.0.0'));
        $this->assertSame('*v^2.0.0', App::normalizeVersion('*v^2.0.0'));
        $this->assertSame('*v^2.0.0(beta)', App::normalizeVersion('*v^2.0.0(beta)'));
        $this->assertSame('\*v^2.0.0(beta)', App::normalizeVersion('\*v^2.0.0(beta)'));

        $this->assertSame('', App::normalizeVersion(''));
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
        $this->assertSame(App::humanizeClass(Entries::class), 'entries');

        $this->assertSame(App::humanizeClass(''), '');
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

    public function testDbConfigHasRequiredIndexes()
    {
        $dbConfig = App::dbConfig();
        $desiredSchemaArray = [
            'class',
            'dsn',
            'password',
            'username',
            'charset',
            'tablePrefix',
            'schemaMap',
            'commandMap',
            'attributes',
            'enableSchemaCache'
        ];

        $this->assertFalse($this->areKeysMissing($dbConfig, $desiredSchemaArray));
   }

   public function testWebRequestConfig()
   {
       $webConfig = App::webRequestConfig();
       $desiredSchemaArray = [
           'class',
           'enableCookieValidation',
           'cookieValidationKey',
           'enableCsrfValidation',
           'enableCsrfCookie',
           'csrfParam',
       ];

       $this->assertFalse($this->areKeysMissing($webConfig, $desiredSchemaArray));
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
