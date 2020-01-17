<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\GqlSchema;
use craft\services\ProjectConfig;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Unit tests for ProjectConfig service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.16
 */
class ProjectConfigTest extends Unit
{
    // Public Properties
    // =========================================================================
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * Test if rebuilding project config ignores the `readOnly` flag.
     */
    public function testRebuildIgnoresReadOnly()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = true;

        $failToSet = function () use ($projectConfig) {
            $projectConfig->set('oops', true);
        };

        // Must trigger exception
        $this->tester->expectThrowable(NotSupportedException::class, $failToSet);

        // Must not trigger exception
        $projectConfig->rebuild();

        $projectConfig->readOnly = $readOnly;
    }

    /**
     * Tests setting a value to project config.
     *
     * @dataProvider setConfigProvider
     * @param $path
     * @param $value
     */
    public function testSettingAndRemovingConfigValue ($path, $value)
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set($path, $value);

        if (is_array($value)) {
            $value = ProjectConfigHelper::cleanupConfig($value);
        }

        $this->assertSame($projectConfig->get($path), $value);

        $projectConfig->remove($path);
        $this->assertNull($projectConfig->get($path));
    }

    public function testDeltasBeingCreated()
    {

    }

    public function getConfigProvider()
    {
        return [
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                true,
                [
                    'a' => null,
                    'b' => 'c',
                    'c' => null,
                ]
            ],
            [
                ['a' => 'b'],
                null,
                ['c' => 'a'],
                true,
                [
                    'a' => 'b',
                    'b' => null,
                    'c' => null,
                ]
            ],
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                false,
                [
                    'a' => null,
                    'b' => null,
                    'c' => 'a',
                ]
            ],
        ];
    }

    public function setConfigProvider ()
    {
        return [
            [
                'a.b.c',
                ['foo' => 'bar']
            ],
            [
                'a.b',
                ['foo' => 'bar', 'bar' => ['baz']]
            ]
        ];
    }
}
