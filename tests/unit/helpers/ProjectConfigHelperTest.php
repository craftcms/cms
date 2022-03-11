<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\FileHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
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
        self::assertSame($packedData, ProjectConfigHelper::packAssociativeArrays($unpackedData));
        self::assertSame($unpackedData, ProjectConfigHelper::unpackAssociativeArrays($packedData));
    }

    /**
     * @dataProvider cleanupConfigDataProvider
     * @param $inputData
     * @param $expectedResult
     */
    public function testCleanupConfig($inputData, $expectedResult)
    {
        self::assertSame($expectedResult, ProjectConfigHelper::cleanupConfig($inputData));
    }

    /**
     * @dataProvider splitIntoComponentsProvider
     * @param $inputData
     * @param $expectedResult
     */
    public function testSplitIntoComponents($inputData, $expectedResult)
    {
        self::assertSame($expectedResult, ProjectConfigHelper::splitConfigIntoComponents($inputData));
    }

    /**
     * @dataProvider touchDataProvider
     * @param string $input
     * @param string $expected
     */
    public function testTouch(string $input, string $expected)
    {
        // Make sure they both end in a newline
        $input = StringHelper::ensureRight($input, "\n");
        $expected = StringHelper::ensureRight($expected, "\n");

        // Make a backup of project.yaml
        $path = Craft::$app->getPath()->getProjectConfigFilePath();
        $backup = $path . '.bak';
        rename($path, $backup);

        // Create a new project.yaml file with the input data
        FileHelper::writeToFile($path, $input);

        // Test
        $timestamp = time();
        $expected = str_replace('__TIMESTAMP__', $timestamp, $expected);
        ProjectConfigHelper::touch($timestamp);
        self::assertSame($expected, file_get_contents($path));

        // Put the old project.yaml back
        FileHelper::unlink($path);
        rename($backup, $path);
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
                        'foo' => ['bar', 'baz'],
                    ],
                    'randomArray' => [1, 7, 2, 'ok'],
                ],
                [
                    'plainSettings' => 'plain',
                    'associativeSettings' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            ['some', 'thing'],
                            ['foo', ['bar', 'baz']],
                        ],
                    ],
                    'randomArray' => [1, 7, 2, 'ok'],
                ],
            ],
            [
                [
                    'test' => [
                        'rootA' => [
                            'label' => 'childA',
                        ],
                        'rootB' => [
                            'label' => 'childB',
                        ],
                    ],
                ],
                [
                    'test' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            [
                                'rootA',
                                [
                                    ProjectConfig::CONFIG_ASSOC_KEY => [
                                        ['label', 'childA'],
                                    ],
                                ],
                            ],
                            [
                                'rootB',
                                [
                                    ProjectConfig::CONFIG_ASSOC_KEY => [
                                        ['label', 'childB'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                    'obj' => (object)['okay'],
                ],
                [
                    'emptier' => '',
                    'gone' => null,
                    'obj' => ['okay'],
                ],
            ],
            [
                [
                    'plainSettings' => 'plain',
                    'other settings' => [
                        'some' => 'thing',
                        'foo' => ['bar', 'baz'],
                    ],
                    'randomArray' => [1, 7, 2, 'ok'],
                ],
                [
                    'other settings' => [
                        'foo' => ['bar', 'baz'],
                        'some' => 'thing',
                    ],
                    'plainSettings' => 'plain',
                    'randomArray' => [1, 7, 2, 'ok'],
                ],
            ],
            // Make sure empty values aren't removed from packed arrays
            // https://github.com/craftcms/cms/issues/7630
            [
                [
                    'a' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            ['foo', []],
                            ['bar'],
                            ['baz', 0],
                        ],
                    ],
                    'b' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            ['foo', []],
                            ['bar'],
                        ],
                    ],
                ],
                [
                    'a' => [
                        ProjectConfig::CONFIG_ASSOC_KEY => [
                            2 => ['baz', 0],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function splitIntoComponentsProvider()
    {
        return [
            [
                [
                    'dateModified' => 1,
                    'email' => [
                        'provider' => 'gmail',
                    ],
                ],
                [
                    'project.yaml' => [
                        'dateModified' => 1,
                        'email' => [
                            'provider' => 'gmail',
                        ],
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 2,
                    'email' => [
                        'provider' => 'gmail',
                        'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                [
                    'project.yaml' => [
                        'dateModified' => 2,
                        'email' => [
                            'provider' => 'gmail',
                            'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                                'key' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 3,
                    'email' => [
                        'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                [
                    'email/aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'key' => 'value',
                    ],
                    'project.yaml' => [
                        'dateModified' => 3,
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 4,
                    'email' => [
                        'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'key' => 'value',
                        ],
                        'bbbbbbbb-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'key2' => 'value',
                        ],
                    ],
                ],
                [
                    'email/aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'key' => 'value',
                    ],
                    'email/bbbbbbbb-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'key2' => 'value',
                    ],
                    'project.yaml' => [
                        'dateModified' => 4,
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 4,
                    'email' => [
                        'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'handle' => 'fooBar',
                        ],
                    ],
                ],
                [
                    'email/fooBar--aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'handle' => 'fooBar',
                    ],
                    'project.yaml' => [
                        'dateModified' => 4,
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 4,
                    'email' => [
                        'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                            'handle' => 'foo-bar',
                        ],
                    ],
                ],
                [
                    'email/aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'handle' => 'foo-bar',
                    ],
                    'project.yaml' => [
                        'dateModified' => 4,
                    ],
                ],
            ],
            [
                [
                    'dateModified' => 4,
                    'commerce' => [
                        'provider' => 'gmail',
                        'productTypes' => [
                            'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                                'key' => 'value',
                            ],
                            'bbbbbbbb-aaaa-4aaa-aaaa-aaaaaaaaaaaa' => [
                                'key2' => 'value',
                            ],
                        ],
                    ],
                ],
                [
                    'commerce/productTypes/aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'key' => 'value',
                    ],
                    'commerce/productTypes/bbbbbbbb-aaaa-4aaa-aaaa-aaaaaaaaaaaa.yaml' => [
                        'key2' => 'value',
                    ],
                    'commerce/commerce.yaml' => [
                        'provider' => 'gmail',
                    ],
                    'project.yaml' => [
                        'dateModified' => 4,
                    ],
                ],
            ],
        ];
    }

    public function touchDataProvider()
    {
        $input1 = <<<EOL
dateModified: 1603054241
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $expected1 = <<<EOL
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $input2 = <<<EOL
<<<<<<< Updated upstream
dateModified: 1603054241
=======
dateModified: 1603054240
>>>>>>> Stashed changes
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $expected2 = <<<EOL
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $input3 = <<<EOL
<<<<<<< Updated upstream
dateModified: 1603054241
foo: bar
=======
dateModified: 1603054240
>>>>>>> Stashed changes
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $expected3 = <<<EOL
dateModified: __TIMESTAMP__
<<<<<<< Updated upstream
foo: bar
=======
>>>>>>> Stashed changes
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $input4 = <<<EOL
<<<<<<< Updated upstream
foo: bar
dateModified: 1603054241
=======
>>>>>>> Stashed changes
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
dateModified: 1603054240
EOL;
        $expected4 = <<<EOL
<<<<<<< Updated upstream
foo: bar
=======
>>>>>>> Stashed changes
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $input5 = <<<EOL
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
        $expected5 = <<<EOL
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
dateModified: __TIMESTAMP__
EOL;
        return [
            [$input1, $expected1],
            [$input2, $expected2],
            [$input3, $expected3],
            [$input4, $expected4],
            [$input5, $expected5],
        ];
    }
}
