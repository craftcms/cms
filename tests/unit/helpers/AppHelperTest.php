<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\config\GeneralConfig;
use craft\enums\CmsEdition;
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

        $variables = [
            'TEST_1' => 'testing1',
            'TEST_2' => 'foo-${TEST_1}-bar',
            'TEST_3' => 'true',
            'TEST_4' => 'false',
            'TEST_EMPTY' => '',
        ];

        foreach ($variables as $name => $value) {
            putenv("$name=$value");
        }

        self::assertSame('testing1', App::env('TEST_1'));
        self::assertSame('foo-testing1-bar', App::env('TEST_2'));
        self::assertTrue(App::env('TEST_3'));
        self::assertFalse(App::env('TEST_4'));
        // todo: this should be assertNull() in v6
        self::assertSame('', App::env('TEST_EMPTY'));

        foreach (array_keys($variables) as $name) {
            putenv($name);
        }

        self::assertSame(CRAFT_TESTS_PATH, App::env('CRAFT_TESTS_PATH'));
        self::assertNull(App::env('TEST_NONEXISTENT_ENV'));
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
        $variables = [
            'TEST_1' => 'testing1',
            'TEST_2' => 'foo${TEST_1}bar',
            'TEST_3' => 'true',
            'TEST_4' => 'false',
            'TEST_ALIAS' => '@vendor/foo/bar',
            'TEST_DEFAULT_SITE_API_KEY' => 'abcdef',
            'TEST_EMPTY' => '',
        ];

        foreach ($variables as $name => $value) {
            putenv("$name=$value");
        }

        self::assertSame('testing1', App::parseEnv('$TEST_1'));
        self::assertSame('testing1', App::parseEnv('${TEST_1}'));
        self::assertSame('testing1/foo/bar', App::parseEnv('$TEST_1/foo/bar'));
        self::assertSame('foo/testing1/bar', App::parseEnv('foo/$TEST_1/bar'));
        self::assertSame('footesting1bar', App::parseEnv('$TEST_2'));
        self::assertSame('footesting1bar', App::parseEnv('${TEST_2}'));
        self::assertSame('foo/footesting1bar/bar', App::parseEnv('foo/$TEST_2/bar'));
        self::assertSame(true, App::parseEnv('$TEST_3'));
        self::assertSame(false, App::parseEnv('$TEST_4'));
        self::assertSame(Craft::getAlias('@vendor/foo/bar'), App::parseEnv('$TEST_ALIAS'));
        self::assertSame('defaultSite', App::parseEnv('$CRAFT_SITE'));
        self::assertSame('DEFAULT_SITE', App::parseEnv('$CRAFT_SITE_UPPER'));
        self::assertSame('abcdef', App::parseEnv('$TEST_${CRAFT_SITE_UPPER}_API_KEY'));
        self::assertSame(CRAFT_TESTS_PATH, App::parseEnv('$CRAFT_TESTS_PATH'));
        self::assertSame(CRAFT_TESTS_PATH . '/foo/bar', App::parseEnv('$CRAFT_TESTS_PATH/foo/bar'));
        self::assertSame('CRAFT_TESTS_PATH', App::parseEnv('CRAFT_TESTS_PATH'));
        self::assertSame(Craft::getAlias('@vendor/foo/bar'), App::parseEnv('@vendor/foo/bar'));
        // todo: this should be assertNull() in v6
        self::assertSame(null, App::parseEnv('$TEST_EMPTY'));
        self::assertNull(App::parseEnv('$TEST_MISSING'));
        self::assertNull(App::parseEnv(null));

        // https://github.com/craftcms/cms/issues/19522
        self::assertSame('$58 million', App::parseEnv('$58 million'));
        self::assertSame('the $58 million', App::parseEnv('the $58 million'));
        self::assertSame('the $58', App::parseEnv('the $58'));
        self::assertSame('/test', App::parseEnv('$58/test'));
        self::assertSame('test//test', App::parseEnv('test/$58/test'));
        self::assertSame('test/', App::parseEnv('test/$58'));

        foreach (array_keys($variables) as $name) {
            putenv($name);
        }
    }

    /**
     * @dataProvider parseBooleanEnvDataProvider
     * @param bool|null $expected
     * @param mixed $value
     */
    public function testParseBooleanEnv(?bool $expected, mixed $value, array $values = []): void
    {
        foreach ($values as $name => $v) {
            putenv("$name=$v");
        }

        self::assertSame($expected, App::parseBooleanEnv($value));
    }

    /**
     * @dataProvider normalizeBooleanValueDataProvider
     * @param bool|null $expected
     * @param mixed $value
     */
    public function testNormalizeBooleanEnv(?bool $expected, mixed $value): void
    {
        self::assertSame($expected, App::normalizeBooleanValue($value));
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
        self::assertEquals([
            CmsEdition::Solo->value,
            CmsEdition::Team->value,
            CmsEdition::Pro->value,
            CmsEdition::Enterprise->value,
        ], App::editions());
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
     *
     */
    public function testSilence(): void
    {
        self::assertSame('foo', App::silence(fn() => 'foo'));
        self::assertNull(App::silence(function() {
        }));
        self::assertNull(App::silence(function(): void {
        }));
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
     * @return array
     */
    public static function envConfigDataProvider(): array
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
    public static function parseBooleanEnvDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [true, 'yes'],
            [true, 'YES'],
            [false, 'no'],
            [true, 'ON'],
            [true, 'on'],
            [false, 'OFF'],
            [false, 'off'],
            [true, 'TRUE'],
            [true, '1'],
            [false, '0'],
            [true, 'true'],
            [false, 'FALSE'],
            [false, 'false'],
            [null, ''],
            [null, 'whatever'],
            [true, 1],
            [false, 0],
            [null, 2],
            [null, '$TEST_MISSING'],
            [
                false,
                '$TEST_FALSE',
                ['TEST_FALSE' => 'false'],
            ],
            [
                false,
                '$TEST_FALSE',
                ['TEST_FALSE' => 'FALSE'],
            ],
            [
                true,
                '$TEST_TRUE',
                ['TEST_TRUE' => 'true'],
            ],
            [
                true,
                '$TEST_TRUE',
                ['TEST_TRUE' => 'TRUE'],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function normalizeBooleanValueDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [true, 'yes'],
            [true, 'YES'],
            [false, 'no'],
            [false, 'no'],
            [true, 'ON'],
            [true, 'on'],
            [false, 'off'],
            [false, 'OFF'],
            [true, '1'],
            [false, '0'],
            [true, 'true'],
            [true, 'TRUE'],
            [false, 'false'],
            [false, 'FALSE'],
            [null, ''],
            [null, 'whatever'],
            [true, 1],
            [false, 0],
            [null, 2],
            [null, null],
        ];
    }

    /**
     * @return array
     */
    public static function editionHandleDataProvider(): array
    {
        return [
            ['solo', CmsEdition::Solo->value],
            ['team', CmsEdition::Team->value],
            ['pro', CmsEdition::Pro->value],
            ['enterprise', CmsEdition::Enterprise->value],
            [false, -1],
        ];
    }

    /**
     * @return array
     */
    public static function editionNameDataProvider(): array
    {
        return [
            ['Solo', CmsEdition::Solo->value],
            ['Team', CmsEdition::Team->value],
            ['Pro', CmsEdition::Pro->value],
            ['Enterprise', CmsEdition::Enterprise->value],
            [false, -1],
        ];
    }

    /**
     * @return array
     */
    public static function editionIdByHandleDataProvider(): array
    {
        return [
            [CmsEdition::Solo->value, 'solo'],
            [CmsEdition::Team->value, 'team'],
            [CmsEdition::Pro->value, 'pro'],
            [CmsEdition::Enterprise->value, 'enterprise'],
            [false, 'personal'],
            [false, 'client'],
        ];
    }

    /**
     * @return array
     */
    public static function validEditionsDataProvider(): array
    {
        return [
            [true, CmsEdition::Solo->value],
            [true, CmsEdition::Team->value],
            [true, CmsEdition::Pro->value],
            [true, CmsEdition::Enterprise->value],
            [true, '1'],
            [true, 0],
            [true, 1],
            [true, 2],
            [false, true],
            [false, null],
            [false, false],
            [false, 4],
        ];
    }

    /**
     * @return array
     */
    public static function configsDataProvider(): array
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
    public static function phpSizeToBytesDataProvider(): array
    {
        return [
            [1, '1B'],
            [1024, '1K'],
            [1024 ** 2, '1M'],
            [1024 ** 3, '1G'],
        ];
    }

    /**
     * @return array
     */
    public static function humanizeClassDataProvider(): array
    {
        return [
            ['entries', Entries::class],
            ['app helper test', self::class],
            ['std class', stdClass::class],
        ];
    }

    /**
     * @return array
     */
    public static function normalizeValueDataProvider(): array
    {
        return [
            [true, 'true'],
            [true, 'TRUE'],
            [false, 'false'],
            [false, 'FALSE'],
            [123, '123'],
            ['123 ', '123 '],
            [' 123', ' 123'],
            [123.4, '123.4'],
            ['foo', 'foo'],
            [null, null],
            ['2833563543.1341693581393', '2833563543.1341693581393'], // https://github.com/craftcms/cms/issues/15533
        ];
    }

    /**
     * @return array
     */
    public static function normalizeVersionDataProvider(): array
    {
        return [
            ['21', 'version 21'],
            ['120.19.2', 'v120.19.2--beta'],
            ['', 'version'],
            ['2', '2\0\0'],
            ['2', '2+2+2'],
            ['2', '2-0-0'],
            ['', '~2'],
            ['', ''],
            ['', '\*v^2.0.0(beta)'],
            ['2.0.0-alpha', '2.0.0-alpha+foo'],
            ['2.0.0-alpha', '2.0.0-alpha.+foo'],
            ['2.0.0-alpha.10', '2.0.0-alpha.10+foo'],
            ['10.5.13', '5.5.5-10.5.13-MariaDB-1:10.5.13+maria~focal-log'],
            ['10.3.38', '10.3.38-MariaDB-1:10.3.38+maria~ubu2004-log'],
            ['5.5.5', '5.5.5-ubuntu-20.04'],
            ['10.3.38', '5.5.5-10.3.38-ubuntu-20.04'],
            ['5.7.16', '5.7.16-0ubuntu0.16.04.1'],
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
