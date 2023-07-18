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
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the App Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AppHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider parseBooleanEnvDataProvider
     *
     * @param bool|null $expected
     * @param mixed $value
     */
    public function testParseBooleanEnv(?bool $expected, $value)
    {
        self::assertSame($expected, App::parseBooleanEnv($value));
    }

    /**
     *
     */
    public function testEditions()
    {
        self::assertEquals([Craft::Solo, Craft::Pro], App::editions());
    }

    /**
     * @dataProvider editionHandleDataProvider
     *
     * @param string|false $expected
     * @param int $edition
     */
    public function testEditionHandle($expected, int $edition)
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            App::editionHandle($edition);
        } else {
            self::assertSame($expected, App::editionHandle($edition));
        }
    }

    /**
     * @dataProvider editionNameDataProvider
     *
     * @param string|false $expected
     * @param int $edition
     */
    public function testEditionName($expected, int $edition)
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            App::editionName($edition);
        } else {
            self::assertSame($expected, App::editionName($edition));
        }
    }

    /**
     * @dataProvider editionIdByHandleDataProvider
     *
     * @param int|false $expected
     * @param string $handle
     */
    public function testEditionIdByHandle($expected, string $handle)
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            App::editionIdByHandle($handle);
        } else {
            self::assertSame($expected, App::editionIdByHandle($handle));
        }
    }

    /**
     * @dataProvider validEditionsDataProvider
     *
     * @param bool $expected
     * @param mixed $edition
     */
    public function testIsValidEdition(bool $expected, $edition)
    {
        self::assertSame($expected, App::isValidEdition($edition));
    }

    /**
     * @dataProvider normalizeVersionDataProvider
     *
     * @param string $expected
     * @param string $version
     */
    public function testNormalizeVersion(string $expected, string $version)
    {
        self::assertSame($expected, App::normalizeVersion($version));
    }

    /**
     *
     */
    public function testPhpConfigValueAsBool()
    {
        $displayErrorsValue = ini_get('display_errors');
        @ini_set('display_errors', 1);
        self::assertTrue(App::phpConfigValueAsBool('display_errors'));
        @ini_set('display_errors', $displayErrorsValue);

        $timezoneValue = ini_get('date.timezone');
        @ini_set('date.timezone', Craft::$app->getTimeZone() ?: 'Europe/Amsterdam');
        self::assertFalse(App::phpConfigValueAsBool('date.timezone'));
        @ini_set('date.timezone', $timezoneValue);

        self::assertFalse(App::phpConfigValueAsBool(''));
        self::assertFalse(App::phpConfigValueAsBool('This is not a config value'));
    }

    /**
     *
     */
    public function testNormalizePhpPaths()
    {
        self::assertSame([getcwd()], App::normalizePhpPaths('.'));
        self::assertSame([getcwd()], App::normalizePhpPaths('./'));
        self::assertSame([getcwd() . DIRECTORY_SEPARATOR . 'foo'], App::normalizePhpPaths('./foo'));
        self::assertSame([getcwd() . DIRECTORY_SEPARATOR . 'foo'], App::normalizePhpPaths('.\\foo'));

        putenv('TEST_CONST=/foo/');
        self::assertSame([getcwd(), DIRECTORY_SEPARATOR . 'foo'], App::normalizePhpPaths('.:${TEST_CONST}'));
        self::assertSame([getcwd(), DIRECTORY_SEPARATOR . 'foo'], App::normalizePhpPaths(' . ; ${TEST_CONST} '));
        putenv('TEST_CONST');
    }

    /**
     * @dataProvider phpSizeToBytesDataProvider
     *
     * @param int|float $expected
     * @param string $value
     */
    public function testPhpSizeToBytes($expected, string $value)
    {
        self::assertSame($expected, App::phpSizeToBytes($value));
    }

    /**
     * @dataProvider humanizeClassDataProvider
     *
     * @param string $expected
     * @param string $class
     */
    public function testHumanizeClass(string $expected, string $class)
    {
        self::assertSame($expected, App::humanizeClass($class));
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

        if (@ini_set('memory_limit', '256M') === false) {
            $this->markTestSkipped('Unable to set memory_limit');
        }

        App::maxPowerCaptain();

        self::assertSame($generalConfig->phpMaxMemoryLimit, ini_get('memory_limit'));
        self::assertSame('0', ini_get('max_execution_time'));

        ini_set('memory_limit', $oldMemoryLimit);
        ini_set('max_execution_time', $oldMaxExecution);
    }

    /**
     * @todo More needed here to test with constant and invalid file path.
     * See coverage report for more info.
     */
    public function testLicenseKey()
    {
        self::assertSame(250, strlen(App::licenseKey()));
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

        self::assertFalse($this->_areKeysMissing($config, $desiredConfig));

        // Make sure we aren't passing in anything unknown or invalid.
        self::assertTrue(class_exists($config['class']));

        // Make sure its a component
        self::assertContains(Component::class, class_parents($config['class']));
    }

    /**
     * Mailer config now needs a mail settings
     */
    public function testMailerConfigIndexes()
    {
        $mailSettings = new MailSettings(['transportType' => Sendmail::class]);
        $result = App::mailerConfig($mailSettings);

        self::assertFalse($this->_areKeysMissing($result, ['class', 'messageClass', 'from', 'template', 'transport']));

        // Make sure its a component
        self::assertContains(Component::class, class_parents($result['class']));
        self::assertTrue(class_exists($result['class']));
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

    /**
     * @return array
     */
    public function parseBooleanEnvDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [true, 'yes'],
            [false, 'no'],
            [true, 'on'],
            [false, 'off'],
            [true, '1'],
            [false, '0'],
            [true, 'true'],
            [false, 'false'],
            [false, ''],
            [null, 'whatever'],
            [true, 1],
            [false, 0],
            [null, 2],
        ];
    }

    /**
     * @return array
     */
    public function editionHandleDataProvider(): array
    {
        return [
            ['solo', Craft::Solo],
            ['pro', Craft::Pro],
            [false, -1],
        ];
    }

    /**
     * @return array
     */
    public function editionNameDataProvider(): array
    {
        return [
            ['Solo', Craft::Solo],
            ['Pro', Craft::Pro],
            [false, -1],
        ];
    }

    /**
     * @return array
     */
    public function editionIdByHandleDataProvider(): array
    {
        return [
            [Craft::Solo, 'solo'],
            [Craft::Pro, 'pro'],
            [false, 'personal'],
            [false, 'client'],
        ];
    }

    /**
     * @return array
     */
    public function validEditionsDataProvider(): array
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
            ['webRequestConfig', ['class', 'enableCookieValidation', 'cookieValidationKey', 'enableCsrfValidation', 'enableCsrfCookie', 'csrfParam', ]],
            ['cacheConfig', ['class', 'cachePath', 'fileMode', 'dirMode', 'defaultDuration']],
            ['mutexConfig', ['class', 'fileMode', 'dirMode']],
            ['logConfig', ['class']],
            ['sessionConfig', ['class', 'flashParam', 'authAccessParam', 'name', 'cookieParams']],
            ['userConfig', ['class', 'identityClass', 'enableAutoLogin', 'autoRenewCookie', 'loginUrl', 'authTimeout', 'identityCookie', 'usernameCookie', 'idParam', 'authTimeoutParam', 'absoluteAuthTimeoutParam', 'returnUrlParam']],
        ];
    }

    /**
     * @return array
     */
    public function phpSizeToBytesDataProvider(): array
    {
        return [
            [1, '1B'],
            [1024, '1K'],
            [pow(1024, 2), '1M'],
            [pow(1024, 3), '1G'],
        ];
    }

    /**
     * @return array
     */
    public function humanizeClassDataProvider(): array
    {
        return [
            ['entries', Entries::class],
            ['app helper test', self::class],
            ['std class', stdClass::class],
            ['iam not a class!@#$%^&*()1234567890', 'iam not a CLASS!@#$%^&*()1234567890'],
        ];
    }

    /**
     * @return array
     */
    public function normalizeVersionDataProvider(): array
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
