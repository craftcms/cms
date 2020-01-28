<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;

class ProjectConfigHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider packedUnpackedDataProvider
     *
     * @param array $field
     * @param array $expectedResult
     */
    public function testAssociativeArrayConfigTransforms($unpackedData, $packedData)
    {
        $this->assertSame($packedData, ProjectConfigHelper::packAssociativeArrays($unpackedData));
        $this->assertSame($unpackedData, ProjectConfigHelper::unpackAssociativeArrays($packedData));
    }

    /**
     * @dataProvider cleanupConfigDataProvider
     * @param $inputData
     * @param $expectedResult
     */
    public function testCleanupConfig($inputData, $expectedResult)
    {
        $this->assertSame($expectedResult, ProjectConfigHelper::cleanupConfig($inputData));
    }


    /**
     * @return array
     */
    public function packedUnpackedDataProvider(): array
    {
        return [
            [
                [
                    'plainSettings' => 'plain',
                    'associativeSettings' => [
                        'some' => 'thing',
                        'foo' => ['bar', 'baz']
                    ],
                    'randomArray' => [1, 7, 2, 'ok']
                ],
                [
                    'plainSettings' => 'plain',
                    'associativeSettings' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            ['some', 'thing'],
                            ['foo', ['bar', 'baz']]
                        ]
                    ],
                    'randomArray' => [1, 7, 2, 'ok']
                ]
            ],
        ];
    }

    public function cleanupConfigDataProvider()
    {
        return [
            [
                [
                    'empty' => [],
                    'emptier' => '',
                    'gone' => null,
                    'obj' => (object) ['okay'],
                ],
                [
                    'emptier' => '',
                    'gone' => null,
                    'obj' => ['okay']
                ],
            ],
            [
                [
                    'plainSettings' => 'plain',
                    'other settings' => [
                        'some' => 'thing',
                        'foo' => ['bar', 'baz']
                    ],
                    'randomArray' => [1, 7, 2, 'ok']
                ],
                [
                    'other settings' => [
                        'foo' => ['bar', 'baz'],
                        'some' => 'thing'
                    ],
                    'plainSettings' => 'plain',
                    'randomArray' => [1, 7, 2, 'ok']
                ],
            ]
        ];
    }
}
