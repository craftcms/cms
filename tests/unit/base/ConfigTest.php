<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\base;

use Codeception\Test\Unit;
use Craft;
use craft\test\mockclasses\models\ExampleModel;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Unit tests for ConfigTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class ConfigTest extends Unit
{

    /**
     * Test config for environment variable overrides.
     *
     * @dataProvider overridesDataProvider
     *
     * @param array $param
     * @param array $override
     * @param mixed $expected
     */
    public function testEnvironmentVariableOverride(array $param, array $override, mixed $expected): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->{$param[0]} = $param[1];

        putenv("$override[0]=$override[1]");

        $generalConfig->normalize();

        self::assertEquals($expected, $generalConfig->{$param[0]});

        // Cleanup env for subsequent tests
        putenv((string)$override[0]);
    }

    /**
     * @return array
     */
    public function overridesDataProvider(): array
    {
        return [
            [
                ['allowAdminChanges', true],
                ['CRAFT_ALLOW_ADMIN_CHANGES', 'false'],
                false,
            ],
            [
                ['disabledPlugins', '*'],
                ['CRAFT_DISABLED_PLUGINS', 'foo,bar'],
                ['foo', 'bar'],
            ],
            [
                ['disabledPlugins', ['foo', 'bar']],
                ['CRAFT_DISABLED_PLUGINS', '*'],
                '*',
            ]
        ];
    }
}
