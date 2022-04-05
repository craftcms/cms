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
        [$paramName, $paramValue] = $param;
        [$overrideName, $overrideValue] = $override;
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->$paramName = $paramValue;

        $envString = $overrideName;

        if ($overrideValue !== null) {
            $envString .= "=$overrideValue";
        }

        putenv($envString);

        $generalConfig->normalize();

        self::assertEquals($expected, $generalConfig->$paramName);

        // Cleanup env for subsequent tests
        putenv((string)$overrideName);
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
                ['allowAdminChanges', true],
                ['CRAFT_ALLOW_ADMIN_CHANGES', null],
                true,
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
            ],
            [
                ['defaultWeekStartDay', 0],
                ['CRAFT_DEFAULT_WEEK_START_DAY', '1'],
                1,
            ],
            [
                ['loginPath', 'login'],
                ['CRAFT_LOGIN_PATH', 'login,with,comma'],
                'login,with,comma',
            ],
            [
                ['loginPath', 'login'],
                ['CRAFT_LOGIN_PATH', 'false'],
                false,
            ],
        ];
    }
}
