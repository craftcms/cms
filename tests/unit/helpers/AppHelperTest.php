<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\config\GeneralConfig;
use craft\helpers\App;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use craft\services\Entries;
use craft\test\TestCase;
use stdClass;
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
     *
     */
    public function testEnv(): void
    {
        $_SERVER['TEST_SERVER_ENV'] = 'server';
        self::assertSame('server', App::env('TEST_SERVER_ENV'));
        unset($_SERVER['TEST_SERVER_ENV']);

        putenv('TEST_GETENV_ENV=getenv');
        self::assertSame('getenv', App::env('TEST_GETENV_ENV'));
        putenv('TEST_GETENV_ENV');

        putenv('TEST_GETENV_TRUE_ENV=true');
        self::assertSame(true, App::env('TEST_GETENV_TRUE_ENV'));
        putenv('TEST_GETENV_TRUE_ENV');

        putenv('TEST_GETENV_FALSE_ENV=false');
        self::assertSame(false, App::env('TEST_GETENV_FALSE_ENV'));
        putenv('TEST_GETENV_FALSE_ENV');

        self::assertSame(CRAFT_TESTS_PATH, App::env('CRAFT_TESTS_PATH'));
        self::assertSame(null, App::env('TEST_NONEXISTENT_ENV'));
    }

    /**
     * @dataProvider envConfigDataProvider
     *
     * @param mixed $expected
     * @param string $paramName
     * @param string $overrideName
     * @param mixed $overrideValue
     */
    public function testEnvConfig(mixed $expected, string $paramName, string $overrideName, mixed $overrideValue): void
    {
        $envString = $overrideName;

        if ($overrideValue !== null) {
            $envString .= "=$overrideValue";
        }

        putenv($envString);

        $config = App::envConfig(GeneralConfig::class, 'CRAFT_');
        if ($expected === null) {
            self::assertArrayNotHasKey($paramName, $config);
        } else {
            self::assertArrayHasKey($paramName, $config);
            self::assertEquals($expected, $config[$paramName]);
        }

        // Cleanup env for subsequent tests
        putenv($overrideName);
    }

    /**
     *
     */
    public function testParseEnv(): void
    {
        self::assertSame(null, App::parseEnv(null));
        self::assertSame(CRAFT_TESTS_PATH, App::parseEnv('$CRAFT_TESTS_PATH'));
        self::assertSame('CRAFT_TESTS_PATH', App::parseEnv('CRAFT_TESTS_PATH'));
        self::assertSame('$TEST_MISSING', App::parseEnv('$TEST_MISSING'));
        self::assertSame(Craft::getAlias('@vendor/foo'), App::parseEnv('@vendor/foo'));
    }

    /**
     * @dataProvider parseBooleanEnvDataProvider
     * @param bool|null $expected
     * @param mixed $value
     */
    public function testParseBooleanEnv(?bool $expected, mixed $value): void
    {
        self::assertSame($expected, App::parseBooleanEnv($value));
    }

    /**
     *
     */
    public function testCliOption(): void
    {
        $argv = $_SERVER['argv'] ?? null;
        $_SERVER['argv'] = [
            'backup',
            'some/path',
            '--file-path=foo.sql',
            '-f',
            'bar.sql',
            '--zip',
            '--falsy=false',
            '--empty=',
        ];
        $length = count($_SERVER['argv']);

        self::assertSame('foo.sql', App::cliOption('--file-path'));
        self::assertSame('bar.sql', App::cliOption('-f', true));
        self::assertSame(true, App::cliOption('--zip'));
        self::assertSame(false, App::cliOption('--falsy'));
        self::assertSame('', App::cliOption('--empty'));
        self::assertSame(null, App::cliOption('--nully'));

        // `-f` and `bar.sql` should have been removed
        self::assertSame($length - 2, count($_SERVER['argv']));

        if ($argv !== null) {
            $_SERVER['argv'] = $argv;
        } else {
            unset($_SERVER['argv']);
        }

        self::expectException(InvalidArgumentException::class);
        App::cliOption('no-dash');
    }

    /**
     *
     */
    public function testEditions(): void
    {
        self::assertEquals([Craft::Solo, Craft::Pro], App::editions());
    }

    /**
     * @dataProvider editionHandleDataProvider
     * @param string|false $expected
     * @param int $edition
     */
    public function testEditionHandle(string|false $expected, int $edition): void
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
     * @param string|false $expected
     * @param int $edition
     */
    public function testEditionName(string|false $expected, int $edition): void
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
     * @param int|false $expected
     * @param string $handle
     */
    public function testEditionIdByHandle(int|false $expected, string $handle): void
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
     * @param bool $expected
     * @param mixed $edition
     */
    public function testIsValidEdition(bool $expected, mixed $edition): void
    {
        self::assertSame($expected, App::isValidEdition($edition));
    }

    /**
     * @dataProvider normalizeValueDataProvider
     */
    public function testNormalizeValue(mixed $expected, mixed $value): void
    {
        self::assertSame($expected, App::normalizeValue($value));
    }

    /**
     * @dataProvider normalizeVersionDataProvider
     * @param string $expected
     * @param string $version
     */
    public function testNormalizeVersion(string $expected, string $version): void
    {
        self::assertSame($expected, App::normalizeVersion($version));
    }

    /**
     *
     */
    public function testPhpConfigValueAsBool(): void
    {
        $displayErrorsValue = ini_get('display_errors');
        @ini_set('display_errors', '1');
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
    public function testNormalizePhpPaths(): void
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
     * @param int|float $expected
     * @param string $value
     */
    public function testPhpSizeToBytes(int|float $expected, string $value): void
    {
        self::assertSame($expected, App::phpSizeToBytes($value));
    }

    /**
     * @dataProvider humanizeClassDataProvider
     * @param string $expected
     * @param string $class
     * @phpstan-param class-string $class
     */
    public function testHumanizeClass(string $expected, string $class): void
    {
        self::assertSame($expected, App::humanizeClass($class));
    }

    /**
     * @todo 3.1 added new functions to test.
     */
    public function testMaxPowerCaptain(): void
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
    public function testLicenseKey(): void
    {
        self::assertSame(250, strlen(App::licenseKey()));
    }

    /**
     * @dataProvider configsDataProvider
     * @param string $method
     * @param array $desiredConfig
     */
    public function testConfigIndexes(string $method, array $desiredConfig): void
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
    public function testMailerConfigIndexes(): void
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
    public function testViewConfigIndexes(): void
    {
        $this->setInaccessibleProperty(Craft::$app->getRequest(), '_isCpRequest', true);
        $this->testConfigIndexes('viewConfig', ['class', 'registeredAssetBundles', 'registeredJsFiles']);

        $this->setInaccessibleProperty(Craft::$app->getRequest(), '_isCpRequest', false);
        $this->testConfigIndexes('viewConfig', ['class']);
    }

    /**
     * @return array
     */
    public function envConfigDataProvider(): array
    {
        return [
            [
                false,
                'allowAdminChanges',
                'CRAFT_ALLOW_ADMIN_CHANGES',
                'false',
            ],
            [
                null,
                'allowAdminChanges',
                'CRAFT_ALLOW_ADMIN_CHANGES',
                null,
            ],
            [
                'foo,bar',
                'disabledPlugins',
                'CRAFT_DISABLED_PLUGINS',
                'foo,bar',
            ],
            [
                '*',
                'disabledPlugins',
                'CRAFT_DISABLED_PLUGINS',
                '*',
            ],
            [
                1,
                'defaultWeekStartDay',
                'CRAFT_DEFAULT_WEEK_START_DAY',
                '1',
            ],
            [
                'login,with,comma',
                'loginPath',
                'CRAFT_LOGIN_PATH',
                'login,with,comma',
            ],
            [
                false,
                'loginPath',
                'CRAFT_LOGIN_PATH',
                'false',
            ],
        ];
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
            ['mutexConfig', ['class', 'fileMode', 'dirMode']],
            ['webRequestConfig', ['class', 'enableCookieValidation', 'cookieValidationKey', 'enableCsrfValidation', 'enableCsrfCookie', 'csrfParam', ]],
            ['cacheConfig', ['class', 'cachePath', 'fileMode', 'dirMode', 'defaultDuration']],
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
            ['iam not a class!@#$%^&*() 1234567890', 'iam not a CLASS!@#$%^&*()1234567890'],
        ];
    }

    /**
     * @return array
     */
    public function normalizeValueDataProvider(): array
    {
        return [
            [true, 'true'],
            [true, 'TRUE'],
            [false, 'false'],
            [false, 'FALSE'],
            [123, '123'],
            [123, '123 '],
            [123, ' 123'],
            [123.4, '123.4'],
            ['foo', 'foo'],
            [null, null],
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
