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
        $generalConfig->normalize();

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
