<?php

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use craft\helpers\App;
use craft\services\Entries;
use CraftCms\Cms\Config\ConstAdapter;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Env;
use InvalidArgumentException;
use Orchestra\Testbench\PHPUnit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

final class AppHelperTest extends TestCase
{
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

    public function testParseEnv(): void
    {
        /** @see \CraftCms\Cms\Config\ConfigServiceProvider */
        Env::extend(fn() => ConstAdapter::class);

        if (!defined('CRAFT_TESTS_PATH')) {
            define('CRAFT_TESTS_PATH', __DIR__);
        }

        self::assertNull(App::parseEnv(null));
        self::assertSame(CRAFT_TESTS_PATH, App::parseEnv('$CRAFT_TESTS_PATH'));
        self::assertSame(CRAFT_TESTS_PATH . '/foo/bar', App::parseEnv('$CRAFT_TESTS_PATH/foo/bar'));
        self::assertSame('CRAFT_TESTS_PATH', App::parseEnv('CRAFT_TESTS_PATH'));
        self::assertSame(null, App::parseEnv('$TEST_MISSING'));
    }

    #[DataProvider('parseBooleanEnvDataProvider')]
    public function testParseBooleanEnv(?bool $expected, mixed $value): void
    {
        self::assertSame($expected, App::parseBooleanEnv($value));
    }

    public static function parseBooleanEnvDataProvider(): array
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
            [null, ''],
            [null, 'whatever'],
            [true, 1],
            [false, 0],
            [null, 2],
            [null, '$TEST_MISSING'],
        ];
    }

    #[DataProvider('envConfigDataProvider')]
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

    public function testPhpConfigValueAsBool(): void
    {
        $displayErrorsValue = ini_get('display_errors');
        @ini_set('display_errors', '1');
        self::assertTrue(App::phpConfigValueAsBool('display_errors'));
        @ini_set('display_errors', $displayErrorsValue);

        $timezoneValue = ini_get('date.timezone');
        @ini_set('date.timezone', 'Europe/Amsterdam');
        self::assertFalse(App::phpConfigValueAsBool('date.timezone'));
        @ini_set('date.timezone', $timezoneValue);

        self::assertFalse(App::phpConfigValueAsBool(''));
        self::assertFalse(App::phpConfigValueAsBool('This is not a config value'));
    }

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

    #[DataProvider('phpSizeToBytesDataProvider')]
    public function testPhpSizeToBytes(int|float $expected, string $value): void
    {
        self::assertSame($expected, App::phpSizeToBytes($value));
    }

    public static function phpSizeToBytesDataProvider(): array
    {
        return [
            [1, '1B'],
            [1024, '1K'],
            [1024 ** 2, '1M'],
            [1024 ** 3, '1G'],
        ];
    }

    #[DataProvider('normalizeVersionDataProvider')]
    public function testNormalizeVersion(string $expected, string $version): void
    {
        self::assertSame($expected, App::normalizeVersion($version));
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

    #[DataProvider('humanizeClassDataProvider')]
    public function testHumanizeClass(string $expected, string $class): void
    {
        self::assertSame($expected, App::humanizeClass($class));
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
     *
     */
    public function testEditions(): void
    {
        self::assertEquals([
            Edition::Solo->value,
            Edition::Team->value,
            Edition::Pro->value,
            Edition::Enterprise->value,
        ], App::editions());
    }

    #[DataProvider('editionHandleDataProvider')]
    public function testEditionHandle(string|false $expected, int $edition): void
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            App::editionHandle($edition);
        } else {
            self::assertSame($expected, App::editionHandle($edition));
        }
    }

    public static function editionHandleDataProvider(): array
    {
        return [
            ['solo', Edition::Solo->value],
            ['team', Edition::Team->value],
            ['pro', Edition::Pro->value],
            ['enterprise', Edition::Enterprise->value],
            [false, -1],
        ];
    }

    #[DataProvider('editionNameDataProvider')]
    public function testEditionName(string|false $expected, int $edition): void
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            App::editionName($edition);
        } else {
            self::assertSame($expected, App::editionName($edition));
        }
    }

    public static function editionNameDataProvider(): array
    {
        return [
            ['Solo', Edition::Solo->value],
            ['Team', Edition::Team->value],
            ['Pro', Edition::Pro->value],
            ['Enterprise', Edition::Enterprise->value],
            [false, -1],
        ];
    }

    #[DataProvider('editionIdByHandleDataProvider')]
    public function testEditionIdByHandle(int|false $expected, string $handle): void
    {
        if ($expected === false) {
            self::expectException(\InvalidArgumentException::class);
            App::editionIdByHandle($handle);
        } else {
            self::assertSame($expected, App::editionIdByHandle($handle));
        }
    }

    public static function editionIdByHandleDataProvider(): array
    {
        return [
            [Edition::Solo->value, 'solo'],
            [Edition::Team->value, 'team'],
            [Edition::Pro->value, 'pro'],
            [Edition::Enterprise->value, 'enterprise'],
            [false, 'personal'],
            [false, 'client'],
        ];
    }

    #[DataProvider('validEditionsDataProvider')]
    public function testIsValidEdition(bool $expected, mixed $edition): void
    {
        self::assertSame($expected, App::isValidEdition($edition));
    }

    public static function validEditionsDataProvider(): array
    {
        return [
            [true, Edition::Solo->value],
            [true, Edition::Team->value],
            [true, Edition::Pro->value],
            [true, Edition::Enterprise->value],
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

    #[DataProvider('normalizeValueDataProvider')]
    public function testNormalizeValue(mixed $expected, mixed $value): void
    {
        self::assertSame($expected, App::normalizeValue($value));
    }

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

    public function testSilence(): void
    {
        self::assertSame('foo', App::silence(fn() => 'foo'));
        self::assertNull(App::silence(function() {
        }));
        self::assertNull(App::silence(function(): void {
        }));
    }
}
