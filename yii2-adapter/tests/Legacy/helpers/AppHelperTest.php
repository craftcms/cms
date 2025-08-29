<?php

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use Craft;
use craft\helpers\App;
use Orchestra\Testbench\PHPUnit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppHelperTest extends TestCase
{
    public function testParseEnv(): void
    {
        if (! defined('CRAFT_TESTS_PATH')) {
            define('CRAFT_TESTS_PATH', __DIR__);
        }

        self::assertNull(App::parseEnv(null));
        self::assertSame(CRAFT_TESTS_PATH, App::parseEnv('$CRAFT_TESTS_PATH'));
        self::assertSame(CRAFT_TESTS_PATH . '/foo/bar', App::parseEnv('$CRAFT_TESTS_PATH/foo/bar'));
        self::assertSame('CRAFT_TESTS_PATH', App::parseEnv('CRAFT_TESTS_PATH'));
        self::assertSame(null, App::parseEnv('$TEST_MISSING'));
        self::assertSame(Craft::getAlias('@vendor/foo/bar'), App::parseEnv('@vendor/foo/bar'));
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
}
