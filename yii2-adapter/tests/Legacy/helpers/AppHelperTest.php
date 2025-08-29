<?php

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use craft\helpers\App;
use CraftCms\Cms\Config\ConstAdapter;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Env;
use Orchestra\Testbench\PHPUnit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppHelperTest extends TestCase
{
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
            [false, ''],
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
}
