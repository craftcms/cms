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
 * @since 4.0.0
 */
class ProjectConfigTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @return ProjectConfig|mixed|\PHPUnit\Framework\MockObject\MockObject
     * @throws \Exception
     */
    protected function getProjectConfig(array $internal = null, array $external = null)
    {
        $internal = $internal ?? [
                'a' => 'b',
                'b' => [
                    'c' => 'd'
                ],
                'e' => [1, 2, 3],
                'f' => 'g'
            ];

        $external = $external ?? [
                'aa' => 'bb',
                'bb' => [
                    'vc' => 'dd'
                ],
                'ee' => [11, 22, 33],
                'f' => 'g'
            ];

        $projectConfig = $this->make(ProjectConfig::class, [
            'getConfigFromYaml' => function() use (&$projectConfig, $external) {
                if ($this->invokeMethod($projectConfig, 'hasAppliedConfig')) {
                    return $this->invokeMethod($projectConfig, 'getAppliedConfig');
                }

                return $external;
            },
            'getConfigFromDb' => $internal
        ]);

        return $projectConfig;
    }

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
     * @param $incomingData
     * @param $expectedResult
     * @dataProvider encodeTestDataProvider
     */
    public function testEncodeData($incomingData, $expectedResult)
    {
        $projectConfig = Craft::$app->getProjectConfig();
        self::assertSame($expectedResult, $this->invokeMethod($projectConfig, 'encodeValueAsString', [$incomingData]));
    }

    /**
     * @param $path
     * @param $useExternal
     * @param $expectedValue
     * @dataProvider getValueDataProvider
     */
    public function testGettingValue($path, $useExternal, $expectedValue)
    {
        self::assertSame($expectedValue, $this->getProjectConfig()->get($path, $useExternal));
    }

    /**
     * @param $path
     * @param $value
     * @param $useExternal
     * @dataProvider setValueDataProvider
     */
    public function testSettingValue($path, $value)
    {
        $projectConfig = $this->getProjectConfig();
        $projectConfig->set($path, $value);

        $actual = $projectConfig->get($path);
        self::assertSame($value, $actual);
    }

    public function testSettingNewValueModifiesTimestamp()
    {
        $pc = Craft::$app->getProjectConfig();
        $systemName = $pc->get('system.name');
        $dateModified = $pc->get('dateModified');

        $pc->set('system.name', $systemName);
        self::assertSame($dateModified, $pc->get('dateModified'));

        $pc->set('system.name', str_rot13($systemName));
        self::assertNotSame($dateModified, $pc->get('dateModified'));
    }

    public function testSettingValueIgnoresExternalValue()
    {
        $internal = [
            'common' => [
                'foo' => 'bar',
                'bar' => 'baz'
            ]
        ];

        $external = [
            'common' => [
                'box' => 'bax',
            ]
        ];
        $pc = $this->getProjectConfig($internal, $external);

        $pc->set('common.fizz', 'buzz');

        // Expect project config to have the merged value
        self::assertSame('buzz', $pc->get('common.fizz'));
        self::assertSame('bar', $pc->get('common.foo'));

        // Expect the external storage to be unaware of anything
        self::assertSame('bax', $pc->get('common.box', true));
        self::assertSame(null, $pc->get('common.fizz', true));
    }

    public function testPreventChangesIfReadOnly()
    {
        $pc = $this->getProjectConfig();
        $pc->readOnly = true;
        $this->expectExceptionMessage('while in read-only');
        $pc->set('path', 'value');

    }

    public function testSettingValueChangesTimestamp()
    {
        $pc = $this->getProjectConfig();
        $timestamp = $pc->get('dateModified');
        $pc->set('path', 'value');
        self::assertNotSame($timestamp, $pc->get('dateModified'));
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

    public function getValueDataProvider()
    {
        return [
            ['a', false, 'b'],
            ['aa', false, null],
            ['aa', true, 'bb'],
            ['b', false, ['c' => 'd']],
            ['b.c', false, 'd'],
            ['ee.1', true, 22],
            ['ee', true, [11, 22, 33]],
            [null, true, $this->externalConfig],
        ];
    }

    public function setValueDataProvider()
    {
        return [
            ['a', 'bar'],
            ['x', ['a' => 'b']],
            ['f', null],
        ];
    }
}
