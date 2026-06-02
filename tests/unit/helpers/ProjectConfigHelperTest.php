<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

class ProjectConfigHelperTest extends TestCase
{
    /**
     * @dataProvider packedUnpackedDataProvider
     * @param array $unpackedData
     * @param array $packedData
     */
    public function testAssociativeArrayConfigTransforms(array $unpackedData, array $packedData): void
    {
        self::assertSame($packedData, ProjectConfigHelper::packAssociativeArrays($unpackedData));
        self::assertSame($unpackedData, ProjectConfigHelper::unpackAssociativeArrays($packedData));
    }

    /**
     * @dataProvider cleanupConfigDataProvider
     * @param array $inputData
     * @param array $expectedResult
     */
    public function testCleanupConfig(array $inputData, array $expectedResult): void
    {
        self::assertSame($expectedResult, ProjectConfigHelper::cleanupConfig($inputData));
    }

    /**
     * @dataProvider splitIntoComponentsProvider
     * @param array $inputData
     * @param array $expectedResult
     */
    public function testSplitIntoComponents(array $inputData, array $expectedResult): void
    {
        self::assertSame($expectedResult, ProjectConfigHelper::splitConfigIntoComponents($inputData));
    }

    /**
     * @dataProvider touchDataProvider
     * @param string $input
     * @param string $expected
     */
    public function testTouch(string $input, string $expected): void
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
        DateTimeHelper::pause();
        $expected = str_replace('__TIMESTAMP__', (string)DateTimeHelper::currentTimeStamp(), $expected);
        ProjectConfigHelper::touch();
        self::assertSame($expected, file_get_contents($path));
        DateTimeHelper::resume();

        // Put the old project.yaml back
        FileHelper::unlink($path);
        rename($backup, $path);
    }

    /**
     * @param mixed $incomingData
     * @param string $expectedResult
     * @dataProvider encodeTestDataProvider
     */
    public function testEncodeData(mixed $incomingData, string $expectedResult): void
    {
        self::assertSame($expectedResult, ProjectConfigHelper::encodeValueAsString($incomingData));
    }

    /**
     * @dataProvider pathSegmentsDataProvider
     *
     * @param string[]|false $expected
     * @param string $path
     */
    public function testPathSegments(array|false$expected, string $path): void
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            ProjectConfigHelper::pathSegments($path);
        } else {
            self::assertEquals($expected, ProjectConfigHelper::pathSegments($path));
        }
    }

    /**
     * @dataProvider lastPathSegmentDataProvider
     *
     * @param string|false $expected
     * @param string $path
     */
    public function testLastPathSegment(string|false $expected, string $path): void
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            ProjectConfigHelper::lastPathSegment($path);
        } else {
            self::assertEquals($expected, ProjectConfigHelper::lastPathSegment($path));
        }
    }

    /**
     * @dataProvider pathWithoutLastSegmentDataProvider
     *
     * @param string|null|false $expected
     * @param string $path
     */
    public function testPathWithoutLastSegment(string|null|false $expected, string $path): void
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
            ProjectConfigHelper::pathWithoutLastSegment($path);
        } else {
            self::assertEquals($expected, ProjectConfigHelper::pathWithoutLastSegment($path));
        }
    }

    /**
     * @return array
     */
    public static function packedUnpackedDataProvider(): array
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
                        ProjectConfig::ASSOC_KEY => [
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
                        ProjectConfig::ASSOC_KEY => [
                            [
                                'rootA',
                                [
                                    ProjectConfig::ASSOC_KEY => [
                                        ['label', 'childA'],
                                    ],
                                ],
                            ],
                            [
                                'rootB',
                                [
                                    ProjectConfig::ASSOC_KEY => [
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

    public static function cleanupConfigDataProvider(): array
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
                        ProjectConfig::ASSOC_KEY => [
                            ['foo', []],
                            ['bar'],
                            ['baz', 0],
                        ],
                    ],
                    'b' => [
                        ProjectConfig::ASSOC_KEY => [
                            ['foo', []],
                            ['bar'],
                        ],
                    ],
                ],
                [
                    'a' => [
                        ProjectConfig::ASSOC_KEY => [
                            2 => ['baz', 0],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function splitIntoComponentsProvider(): array
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

    public static function encodeTestDataProvider(): array
    {
        return [
            [
                'foo',
                '"foo"',
            ],
            [
                true,
                'true',
            ],
            [
                null,
                'null',
            ],
            [
                false,
                'false',
            ],
            [
                2.5,
                '2.5',
            ],
            [
                0,
                '0',
            ],
            [
                2,
                '2',
            ],
            [
                2.0,
                '2.0',
            ],
        ];
    }

    public static function touchDataProvider(): array
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

    /**
     * @return array[]
     */
    public static function pathSegmentsDataProvider(): array
    {
        return [
            [['foo'], 'foo'],
            [['foo', 'bar'], 'foo.bar'],
            [['foo', 'bar', 'baz'], 'foo.bar.baz'],
            [['foo\\bar', 'baz'], 'foo\\bar.baz'],
            [false, ''],
        ];
    }

    /**
     * @return array[]
     */
    public static function lastPathSegmentDataProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['bar', 'foo.bar'],
            ['baz', 'foo.bar.baz'],
            ['baz', 'foo\\bar.baz'],
            [false, ''],
        ];
    }

    /**
     * @return array[]
     */
    public static function pathWithoutLastSegmentDataProvider(): array
    {
        return [
            [null, 'foo'],
            ['foo', 'foo.bar'],
            ['foo.bar', 'foo.bar.baz'],
            ['foo\\bar', 'foo\\bar.baz'],
            [false, ''],
        ];
    }
}
