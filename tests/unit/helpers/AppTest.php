<?php
namespace craftunit\helpers;

use Codeception\Codecept;
use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\services\Entries;

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

        $this->assertSame('', App::normalizeVersion('', ''));
    }

    public function testPhpConfigValueAsBool()
    {
        $this->assertTrue(App::phpConfigValueAsBool('display_errors'));
        $this->assertFalse(App::phpConfigValueAsBool(''));
        $this->assertFalse(App::phpConfigValueAsBool('This isnt a config value'));
    }

    public function testClassHumanization()
    {
        $this->assertSame(App::humanizeClass(Entries::class), 'entries');

        $this->assertSame(App::humanizeClass(''), '');
        // Make sure non craft classes are normalized. TODO: Depend on class that is auto shipped with the tests and not a codeception class.
        $this->assertSame('app test', App::humanizeClass(self::class));
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
        $licenseKey = App::licenseKey();
        $this->assertSame(250, strlen(App::licenseKey()));

        // TODO: What else to test here?
    }

    public function testDbConfigHasRequiredIndexes()
    {
        // TODO: Do we need all of these?
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

        $this->assertTrue($this->runConfigTest($dbConfig, $desiredSchemaArray));
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

       $this->assertTrue($this->runConfigTest($webConfig, $desiredSchemaArray));
   }

   private function runConfigTest(array $configArray, array $desiredSchemaArray) : bool
   {
       return (bool)array_diff_key($desiredSchemaArray, $configArray);
   }
}
