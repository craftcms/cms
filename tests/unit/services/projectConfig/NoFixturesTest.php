<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Stub\Expected;
use Craft;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use craft\services\Sections;
use craft\test\TestCase;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Unit tests for ProjectConfig service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.16
 */
class ProjectConfigTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Test if rebuilding project config ignores the `readOnly` flag.
     */
    public function testRebuildIgnoresReadOnly()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = true;

        $failToSet = function() use ($projectConfig) {
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
    public function testSettingAndRemovingConfigValue($path, $value)
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

    /**
     * Tests whether setting a config value correctly appears in the database
     */
    public function testConfigChangesPropagatedToDb()
    {
        $yaml = [
            'sections' => ['someUid' => ['handle' => 'someHandle']],
            'sections.someUid' => ['handle' => 'otherHandle'],
            'sections.someUid.handle' => 'otherHandle'
        ];

        $sectionService = $this->make(Sections::class, [
            'handleChangedSection' => Expected::once()
        ]);
        $projectConfig = $this->make(ProjectConfig::class, [
            '_storedConfig' => [
                'sections' => [
                    'someUid' => [
                        'handle' => 'someHandle'
                    ]
                ]
            ],
            'get' => function($path, $useYaml) use ($yaml) {
                return $yaml[$path];
            }
        ]);

        // Mocking the project config killed all event listeners, though
        $projectConfig->init();

        $projectConfig
            ->onAdd(Sections::CONFIG_SECTIONS_KEY . '.{uid}', [$sectionService, 'handleChangedSection'])
            ->onUpdate(Sections::CONFIG_SECTIONS_KEY . '.{uid}', [$sectionService, 'handleChangedSection'])
            ->onRemove(Sections::CONFIG_SECTIONS_KEY . '.{uid}', [$sectionService, 'handleDeletedSection']);


        Craft::$app->set('sections', $sectionService);
        Craft::$app->set('projectConfig', $projectConfig);

        Craft::$app->getProjectConfig()->processConfigChanges('sections.someUid.handle');
    }

    /**
     * @param $incomingData
     * @param $expectedResult
     * @dataProvider encodeTestDataProvider
     */
    public function testEncodeData($incomingData, $expectedResult)
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $this->assertSame($expectedResult, $this->invokeMethod($projectConfig, 'encodeValueAsString', [$incomingData]));
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

    public function setConfigProvider()
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

    public function encodeTestDataProvider()
    {
        return [
            [
                'foo',
                '"foo"'
            ],
            [
                true,
                'true'
            ],
            [
                null,
                'null'
            ],
            [
                false,
                'false'
            ],
            [
                2.5,
                '2.5'
            ],
            [
                0,
                '0'
            ],
            [
                2,
                '2'
            ],
            [
                2.0,
                '2.0'
            ],
        ];
    }
}
