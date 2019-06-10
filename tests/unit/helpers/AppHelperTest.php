<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\App;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use craft\services\Entries;
use craft\test\TestCase;
use stdClass;
use UnitTester;
use yii\base\Component;

/**
 * Unit tests for the App Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AppHelperTest extends TestCase
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testEditions()
    {
        $this->assertEquals([Craft::Solo, Craft::Pro], App::editions());
    }

    /**
     *
     */
    public function testEditionName()
    {
        $this->assertEquals('Solo', App::editionName(Craft::Solo));
        $this->assertEquals('Pro', App::editionName(Craft::Pro));
    }

    /**
     * @dataProvider validEditionsDataProviders
     *
     * @param $result
     * @param $input
     */
    public function testIsValidEdition($result, $input)
    {
        $isValid = App::isValidEdition($input);
        $this->assertSame($result, $isValid);
        $this->assertIsBool($isValid);
    }

    /**
     * @dataProvider versionListDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testVersionNormalization($result, string $input)
    {
        $version = App::normalizeVersion($input);
        $this->assertSame($result, App::normalizeVersion($input));
        $this->assertIsString($version);
    }

    /**
     *
     */
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
        $this->assertFalse(App::phpConfigValueAsBool('This is not a config value'));
    }

    /**
     * @dataProvider classHumanizationDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testClassHumanization($result, $input)
    {
        $humanizedClass = App::humanizeClass($input);
        $this->assertSame($result, $humanizedClass);

        // Make sure we dont have any uppercase characters.
        $this->assertNotRegExp('/[A-Z]/', $humanizedClass);
    }

    /**
     * @todo 3.1 added new functions to test.
     */
    public function testMaxPowerCaptain()
    {
        $oldMemoryLimit = ini_get('memory_limit');
        $oldMaxExecution = ini_get('max_execution_time');

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->phpMaxMemoryLimit = '512M';

        ini_set('memory_limit', '256M');

        App::maxPowerCaptain();

        $this->assertSame($generalConfig->phpMaxMemoryLimit, ini_get('memory_limit'));
        $this->assertSame('0', ini_get('max_execution_time'));

        ini_set('memory_limit', $oldMemoryLimit);
        ini_set('max_execution_time', $oldMaxExecution);
    }

    /**
     * @todo More needed here to test with constant and invalid file path.
     * See coverage report for more info.
     */
    public function testLicenseKey()
    {
        $this->assertSame(250, strlen(App::licenseKey()));
    }

    /**
     * @dataProvider configsDataProvider
     *
     * @param $method
     * @param $desiredConfig
     */
    public function testConfigIndexes($method, $desiredConfig)
    {
        $config = App::$method();

        $this->assertFalse($this->_areKeysMissing($config, $desiredConfig));

        // Make sure we aren't passing in anything unknown or invalid.
        $this->assertTrue(class_exists($config['class']));

        // Make sure its a component
        $this->assertContains(Component::class, class_parents($config['class']));
    }

    /**
     * Mailer config now needs a mail settings
     */
    public function testMailerConfigIndexes()
    {
        $mailSettings = new MailSettings(['transportType' => Sendmail::class]);
        $result = App::mailerConfig($mailSettings);

        $this->assertFalse($this->_areKeysMissing($result, ['class', 'messageClass', 'from', 'template', 'transport']));

        // Make sure its a component
        $this->assertContains(Component::class, class_parents($result['class']));
        $this->assertTrue(class_exists($result['class']));
    }

    /**
     *
     */
    public function testViewConfigIndexes()
    {
        $this->setInaccessibleProperty(Craft::$app->getRequest(), '_isCpRequest', true);
        $this->testConfigIndexes('viewConfig', ['class', 'registeredAssetBundles', 'registeredJsFiles']);

        $this->setInaccessibleProperty(Craft::$app->getRequest(), '_isCpRequest', false);
        $this->testConfigIndexes('viewConfig', ['class']);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function validEditionsDataProviders(): array
    {
        return [
            [true, Craft::Pro],
            [true, Craft::Solo],
            [true, '1'],
            [true, 0],
            [true, 1],
            [true, true],
            [false, null],
            [false, false],
            [false, 4],
            [false, 2],
            [false, 3],
        ];
    }

    /**
     * @return array
     */
    public function configsDataProvider(): array
    {
        return [
            ['assetManagerConfig', ['class', 'basePath', 'baseUrl', 'fileMode', 'dirMode', 'appendTimestamp']],
            ['dbConfig', ['class', 'dsn', 'password', 'username', 'charset', 'tablePrefix', 'schemaMap', 'commandMap', 'attributes', 'enableSchemaCache']],
            ['webRequestConfig', ['class', 'enableCookieValidation', 'cookieValidationKey', 'enableCsrfValidation', 'enableCsrfCookie', 'csrfParam',]],
            ['cacheConfig', ['class', 'cachePath', 'fileMode', 'dirMode', 'defaultDuration']],
            ['mutexConfig', ['class', 'fileMode', 'dirMode']],
            ['logConfig', ['class', 'targets']],
            ['sessionConfig', ['class', 'flashParam', 'authAccessParam', 'name', 'cookieParams']],
            ['userConfig', ['class', 'identityClass', 'enableAutoLogin', 'autoRenewCookie', 'loginUrl', 'authTimeout', 'identityCookie', 'usernameCookie', 'idParam', 'authTimeoutParam', 'absoluteAuthTimeoutParam', 'returnUrlParam']],
        ];
    }

    /**
     * @return array
     */
    public function classHumanizationDataProvider(): array
    {
        return [
            ['entries', Entries::class],
            ['app helper test', self::class],
            ['std class', stdClass::class],
            ['iam not a class!@#$%^&*()1234567890', 'iam not a CLASS!@#$%^&*()1234567890']
        ];
    }

    /**
     * @return array
     */
    public function versionListDataProvider(): array
    {
        return [
            ['version', 'version 21'],
            ['v120.19.2', 'v120.19.2--beta'],
            ['version', 'version'],
            ['2\0\0', '2\0\0'],
            ['2', '2+2+2'],
            ['2', '2-0-0'],
            ['~2', '~2'],
            ['', ''],
            ['\*v^2.0.0(beta)', '\*v^2.0.0(beta)'],

        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * @param array $configArray
     * @param array $desiredSchemaArray
     * @return bool
     */
    private function _areKeysMissing(array $configArray, array $desiredSchemaArray): bool
    {
        foreach ($desiredSchemaArray as $desiredSchemaItem) {
            if (!array_key_exists($desiredSchemaItem, $configArray)) {
                return true;
            }
        }

        return false;
    }
}
