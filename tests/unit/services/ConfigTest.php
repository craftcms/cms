<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\services\Config;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 4.0
 */
class ConfigTest extends TestCase
{
    public function testDotEnvPathIsNotABooleanString(): void
    {
        Craft::setAlias('@dotenv', CRAFT_TESTS_PATH . '/.env');

        $config = Craft::$app->getConfig();
        $path = $config->getDotEnvPath();
        $this->assertEquals(CRAFT_TESTS_PATH . '/.env', $path);
    }

    /**
     * @dataProvider setDotEnvVarThrowsOnNewlineDataProvider
     * @param string $value
     */
    public function testSetDotEnvVarThrowsOnNewline(string $value): void
    {
        $originalAlias = Craft::getAlias('@dotenv');
        $path = tempnam(sys_get_temp_dir(), 'CraftDotEnvTest');
        file_put_contents($path, "FOO=bar\n");

        try {
            Craft::setAlias('@dotenv', $path);
            $config = new Config();

            $this->expectException(InvalidArgumentException::class);
            $config->setDotEnvVar('FOO', $value);
        } finally {
            Craft::setAlias('@dotenv', $originalAlias);
            unlink($path);
        }
    }

    public function setDotEnvVarThrowsOnNewlineDataProvider(): array
    {
        return [
            'newline' => ["bar\nEVIL=1"],
            'carriage return' => ["bar\rEVIL=1"],
            'crlf' => ["bar\r\nEVIL=1"],
            'trailing newline' => ["bar\n"],
        ];
    }
}
