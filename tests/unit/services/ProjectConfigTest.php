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
     * Test retrieving memoized data by path.
     *
     * @dataProvider memoizationDataProvider
     * @param $path
     * @param $config
     * @param $result
     * @throws \Exception
     */
    public function testRetrieveMemoizedData ($path, $config, $result)
    {
        $projectConfig = $this->make(ProjectConfig::class, [
            '_memoizedConfig' => $config,
        ]);

        $reflection = new \ReflectionClass(get_class($projectConfig));
        $method = $reflection->getMethod('_getMemoizedValue');
        $method->setAccessible(true);
        $this->assertSame($result, $method->invokeArgs($projectConfig, [$path]));
    }

    /**
     * Test whether memoizing sets the dependency paths correctly.
     *
     * @dataProvider memoizationDependencyProvider
     * @param $path
     * @param $setPaths
     * @throws \ReflectionException
     */
    public function testMemoizationSetsDependencies ($path, $dependencies) {
        $projectConfig = Craft::$app->getProjectConfig();

        $reflection = new \ReflectionClass(ProjectConfig::class);
        $method = $reflection->getMethod('_memoize');
        $method->setAccessible(true);
        $method->invokeArgs($projectConfig, [$path, null]);

        $property = $reflection->getProperty('_memoizationDependencies');
        $property->setAccessible(true);

        foreach ($dependencies as $dependency) {
            $memoizedDepencies  = $property->getValue($projectConfig);
            $this->assertArrayHasKey($dependency, $memoizedDepencies);
        }
    }

    /**
     * Test if memoization invalidation correctly invalidates dependencies
     *
     * @dataProvider memoizationInvalidationTestProvider
     * @param $paths
     * @param $invalidatePaths
     * @param $removePaths
     * @throws \ReflectionException
     */
    public function testMemoizationInvalidation ($paths, $invalidatePath, $removedPaths, $remainingPaths) {
        $projectConfig = Craft::$app->getProjectConfig();

        $reflection = new \ReflectionClass(ProjectConfig::class);
        $method = $reflection->getMethod('_memoize');
        $method->setAccessible(true);

        // Populate the given paths
        foreach ($paths as $path) {
            $method->invokeArgs($projectConfig, [$path, null]);
        }

        // Invalidate the provided path
        $method = $reflection->getMethod('_invalidateMemoizedData');
        $method->setAccessible(true);
        $method->invokeArgs($projectConfig, [$invalidatePath]);

        // Ensure paths are removed from memoization
        $property = $reflection->getProperty('_memoizedConfig');
        $property->setAccessible(true);
        $memoizedConfig  = $property->getValue($projectConfig);

        foreach ($removedPaths as $removedPath) {
            $this->assertArrayNotHasKey($removedPath, $memoizedConfig);
        }

        // Ensure other paths remain
        foreach ($remainingPaths as $remainingPath) {
            $this->assertArrayHasKey($remainingPath, $memoizedConfig);
        }
    }

    /**
     * Test getting the value fetches it from the correct source
     *
     * @dataProvider getConfigProvider
     * @param $yamlData
     * @param $changesetData
     * @param $configData
     * @param $getFromYaml
     * @param $result
     */
    public function testGettingConfigValue ($yamlData, $changeSetData, $configData, $getFromYaml, $result)
    {
        $projectConfig = $this->make(ProjectConfig::class, [
            '_changesBeingApplied' => $changeSetData,
            '_parsedConfigs' => [Craft::$app->getPath()->getProjectConfigFilePath() => $yamlData],
            '_memoizedConfig' => $configData,
        ]);

        Craft::$app->getConfig()->getGeneral()->useProjectConfigFile = true;

        foreach ($result as $path => $value) {
            $this->assertSame($value, $projectConfig->get($path, $getFromYaml));
        }

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

    // Data providers
    // =========================================================================
    public function memoizationDataProvider()
    {
        return [
            [
                'a.b.c',
                ['a.b.c' => 'value'],
                'value'
            ],
            [
                'a.b.c',
                ['a.b' => ['c' => 'value']],
                'value'
            ],
            [
                'a.b.c',
                ['a' => ['b' => ['c' => 'value']]],
                'value'
            ],
            [
                'a.b.c',
                ['__all__' => ['a' => ['b' => ['c' => 'value']]]],
                'value'
            ],
            [
                'a.b.c',
                ['__all__' => ['a' => ['x' => [['b' => ['c' => 'value']]]]]],
                null
            ],
        ];
    }

    public function memoizationDependencyProvider()
    {
        return [
            [
                'a.b.c',
                ['a', 'a.b']
            ],
            [
                'a.b',
                ['a']
            ],
            [
                'a.b.c.d.e.f',
                ['a', 'a.b', 'a.b.c', 'a.b.c.d', 'a.b.c.d.e']
            ]
        ];
    }

    public function memoizationInvalidationTestProvider()
    {
        return [
            [
                ['a.b', 'a.b.c', 'a', 'a.b.c.d', '__control__'],
                'a.b',
                ['a', 'a.b', 'a.b.c', 'a.b.c.d'],
                ['__control__']
            ],
            [
                ['a', 'b', 'c', 'a.b.c.d', '__control__'],
                'a',
                ['a', 'a.b.c.d'],
                ['__control__', 'b', 'c']
            ],
            [
                ['__control__'],
                'a',
                [],
                ['__control__']
            ],
        ];
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
