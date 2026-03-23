<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\File;

test('associative array config transforms', function (array $unpackedData, array $packedData) {
    expect(ProjectConfigHelper::packAssociativeArrays($unpackedData))->toBe($packedData);
    expect(ProjectConfigHelper::unpackAssociativeArrays($packedData))->toBe($unpackedData);
})->with([
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
]);

test('cleanup config', function (array $inputData, array $expectedResult) {
    expect(ProjectConfigHelper::cleanupConfig($inputData))->toBe($expectedResult);
})->with([
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
]);

test('split into components', function (array $inputData, array $expectedResult) {
    expect(ProjectConfigHelper::splitConfigIntoComponents($inputData))->toBe($expectedResult);
})->with([
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
]);

test('touch', function (string $input, string $expected) {
    // Make sure they both end in a newline
    $input = Str::finish($input, "\n");
    $expected = Str::finish($expected, "\n");

    // Make a backup of project.yaml
    $path = Craft::$app->getPath()->getProjectConfigFilePath();
    if ($exists = file_exists($path)) {
        $backup = $path.'.bak';
        rename($path, $backup);
    }

    // Create a new project.yaml file with the input data
    CraftCms\Cms\Support\File::writeToFile($path, $input);

    // Test
    DateTimeHelper::pause();
    $expected = str_replace('__TIMESTAMP__', (string) DateTimeHelper::currentTimeStamp(), $expected);
    ProjectConfigHelper::touch();
    expect(file_get_contents($path))->toBe($expected);
    DateTimeHelper::resume();

    // Put the old project.yaml back
    File::delete($path);
    if ($exists) {
        rename($backup, $path);
    }
})->with(function () {
    $input1 = <<<'EOL'
dateModified: 1603054241
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $expected1 = <<<'EOL'
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $input2 = <<<'EOL'
dateModified: 1603054241
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $expected2 = <<<'EOL'
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $input3 = <<<'EOL'
dateModified: 1603054241
foo: bar
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $expected3 = <<<'EOL'
dateModified: __TIMESTAMP__
foo: bar
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $input4 = <<<'EOL'
foo: bar
dateModified: 1603054241
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
dateModified: 1603054240
EOL;
    $expected4 = <<<'EOL'
foo: bar
dateModified: __TIMESTAMP__
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $input5 = <<<'EOL'
system:
  edition: pro
  live: true
  name: 'Happy Lager'
  schemaVersion: 3.5.13
  timeZone: UTC
EOL;
    $expected5 = <<<'EOL'
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
});

test('encode', function (mixed $incomingData, string $expectedResult) {
    expect(ProjectConfigHelper::encodeValueAsString($incomingData))->toBe($expectedResult);
})->with([
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
]);

test('path segments', function (array|false $expected, string $path) {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        ProjectConfigHelper::pathSegments($path);

        return;
    }

    expect(ProjectConfigHelper::pathSegments($path))->toBe($expected);
})->with([
    [['foo'], 'foo'],
    [['foo', 'bar'], 'foo.bar'],
    [['foo', 'bar', 'baz'], 'foo.bar.baz'],
    [['foo\\bar', 'baz'], 'foo\\bar.baz'],
    [false, ''],
]);

test('last path segment', function (string|false $expected, string $path) {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        ProjectConfigHelper::lastPathSegment($path);

        return;
    }

    expect(ProjectConfigHelper::lastPathSegment($path))->toBe($expected);
})->with([
    ['foo', 'foo'],
    ['bar', 'foo.bar'],
    ['baz', 'foo.bar.baz'],
    ['baz', 'foo\\bar.baz'],
    [false, ''],
]);

test('path without last segment', function (string|null|false $expected, string $path) {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        ProjectConfigHelper::pathWithoutLastSegment($path);

        return;
    }

    expect(ProjectConfigHelper::pathWithoutLastSegment($path))->toBe($expected);
})->with([
    [null, 'foo'],
    ['foo', 'foo.bar'],
    ['foo.bar', 'foo.bar.baz'],
    ['foo\\bar', 'foo\\bar.baz'],
    [false, ''],
]);
