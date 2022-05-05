<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\config;

use Codeception\Test\Unit;
use Craft;

/**
 * Unit tests for ConfigTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class GeneralConfigTest extends Unit
{
    /**
     * Test config for renamed settings
     *
     * @dataProvider renamedSettingsDataProvider
     *
     * @param string $oldProperty
     * @param string $newProperty
     * @param mixed $value
     */
    public function testRenamedSetting(string $oldProperty, string $newProperty, mixed $value): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->$oldProperty = $value;

        self::assertEquals($value, $generalConfig->$newProperty);
    }

    /**
     * @return array
     */
    public function renamedSettingsDataProvider(): array
    {
        return [
            [
                'activateAccountFailurePath',
                'invalidUserTokenPath',
                'foo',
            ],
            [
                'defaultFilePermissions',
                'defaultFileMode',
                0777,
            ],
        ];
    }
}
