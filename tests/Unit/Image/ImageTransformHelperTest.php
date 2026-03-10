<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Image\ImageTransforms;

describe('getTransformString', function () {
    test('returns handle-based string when handle is set', function () {
        $transform = new ImageTransform(['handle' => 'thumb']);

        expect(ImageTransformHelper::getTransformString($transform))->toBe('_thumb');
    });

    test('returns handle-based string even with dimensions set', function () {
        $transform = new ImageTransform([
            'handle' => 'thumb',
            'width' => 200,
            'height' => 100,
        ]);

        expect(ImageTransformHelper::getTransformString($transform))->toBe('_thumb');
    });

    test('ignores handle when ignoreHandle is true', function () {
        $transform = new ImageTransform([
            'handle' => 'thumb',
            'width' => 200,
            'height' => 100,
            'mode' => 'crop',
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_200x100_crop_center-center_none');
    });

    test('uses AUTO for missing dimensions', function (array $config, string $expected) {
        $transform = new ImageTransform($config);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe($expected);
    })->with([
        'missing height' => [
            ['width' => 300, 'mode' => 'fit'],
            '_300xAUTO_fit_center-center_none',
        ],
        'missing width and height' => [
            ['mode' => 'crop'],
            '_AUTOxAUTO_crop_center-center_none',
        ],
    ]);

    test('includes quality when set', function () {
        $transform = new ImageTransform([
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
            'quality' => 80,
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_100x100_crop_center-center_80_none');
    });

    test('includes fill when set', function () {
        $transform = new ImageTransform([
            'width' => 100,
            'height' => 100,
            'mode' => 'letterbox',
            'fill' => '#ff0000',
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_100x100_letterbox_center-center_none_ff0000');
    });

    test('appends ns when upscale is false', function () {
        $transform = new ImageTransform([
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
            'upscale' => false,
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_100x100_crop_center-center_none_ns');
    });

    test('builds full string with all options', function () {
        $transform = new ImageTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'letterbox',
            'position' => 'top-left',
            'quality' => 90,
            'interlace' => 'line',
            'fill' => '#aabbcc',
            'upscale' => false,
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_800x600_letterbox_top-left_90_line_aabbcc_ns');
    });

    test('falls back to center-center for invalid position', function () {
        $transform = new ImageTransform([
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
            'position' => 'invalid-position',
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_100x100_crop_center-center_none');
    });

    test('matches legacy getTransformString provider cases', function (array $config, string $expected) {
        $transform = new ImageTransform($config);

        expect(ImageTransformHelper::getTransformString($transform))->toBe($expected);
    })->with([
        'basic transform (no upscale)' => [
            [
                'width' => 1200,
                'height' => 900,
                'upscale' => false,
            ],
            '_1200x900_crop_center-center_none_ns',
        ],
        'no width' => [
            [
                'width' => null,
                'height' => 900,
                'upscale' => true,
            ],
            '_AUTOx900_crop_center-center_none',
        ],
        'no height' => [
            [
                'width' => 1200,
                'height' => null,
            ],
            '_1200xAUTO_crop_center-center_none',
        ],
        'no height + explicit upscale true' => [
            [
                'width' => 1200,
                'height' => null,
                'upscale' => true,
            ],
            '_1200xAUTO_crop_center-center_none',
        ],
        'no height + no upscale' => [
            [
                'width' => 1200,
                'height' => null,
                'upscale' => false,
            ],
            '_1200xAUTO_crop_center-center_none_ns',
        ],
        'with handle' => [
            [
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
            ],
            '_testTransform',
        ],
        'full transform' => [
            [
                'handle' => null,
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => false,
            ],
            '_100x200_fit_center-center_95_line_ff0000_ns',
        ],
        'transparent fill' => [
            [
                'handle' => null,
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => 'transparent',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => false,
            ],
            '_100x200_fit_center-center_95_line_transparent_ns',
        ],
    ]);
});

describe('parseTransformString', function () {
    test('parses basic transform string', function (string $transformString) {
        $result = ImageTransformHelper::parseTransformString($transformString);

        expect($result)->toBe([
            'width' => 800,
            'height' => 600,
            'mode' => 'crop',
            'position' => 'center-center',
            'quality' => null,
            'interlace' => 'none',
            'fill' => null,
            'upscale' => true,
        ]);
    })->with([
        'without leading underscore' => ['800x600_crop_center-center_none'],
        'with leading underscore' => ['_800x600_crop_center-center_none'],
    ]);

    test('parses AUTO as null', function () {
        $result = ImageTransformHelper::parseTransformString('AUTOx600_fit_center-center_none');

        expect($result['width'])->toBeNull()
            ->and($result['height'])->toBe(600);
    });

    test('parses option-specific values', function (string $transformString, string $parsedKey, mixed $expected) {
        $result = ImageTransformHelper::parseTransformString($transformString);

        expect($result[$parsedKey])->toBe($expected);
    })->with([
        'quality' => ['800x600_crop_center-center_80_none', 'quality', 80],
        'fill color' => ['800x600_letterbox_center-center_none_ff0000', 'fill', '#ff0000'],
        'transparent fill' => ['800x600_letterbox_center-center_none_transparent', 'fill', 'transparent'],
        'no-upscale' => ['800x600_crop_center-center_none_ns', 'upscale', false],
    ]);

    test('parses all options together', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_letterbox_top-left_90_line_aabbcc_ns');

        expect($result)->toBe([
            'width' => 800,
            'height' => 600,
            'mode' => 'letterbox',
            'position' => 'top-left',
            'quality' => 90,
            'interlace' => 'line',
            'fill' => '#aabbcc',
            'upscale' => false,
        ]);
    });

    test('throws on invalid string', function () {
        ImageTransformHelper::parseTransformString('not-a-transform');
    })->throws(InvalidArgumentException::class, 'Invalid transform string');
});

describe('createTransformFromString', function () {
    test('creates transform from valid string', function () {
        $transform = ImageTransformHelper::createTransformFromString('_800x600_crop_center-center_80_none');

        expect($transform)->toBeInstanceOf(ImageTransform::class)
            ->and($transform->width)->toBe(800)
            ->and($transform->height)->toBe(600)
            ->and($transform->mode)->toBe('crop')
            ->and($transform->position)->toBe('center-center')
            ->and($transform->quality)->toBe(80)
            ->and($transform->interlace)->toBe('none')
            ->and($transform->upscale)->toBeTrue();
    });

    test('handles special transform string flags', function (
        string $transformString,
        ?int $expectedWidth,
        ?int $expectedHeight,
        bool $expectedUpscale
    ) {
        $transform = ImageTransformHelper::createTransformFromString($transformString);

        expect($transform->width)->toBe($expectedWidth)
            ->and($transform->height)->toBe($expectedHeight)
            ->and($transform->upscale)->toBe($expectedUpscale);
    })->with([
        'AUTO width' => ['_AUTOx600_fit_center-center_none', null, 600, true],
        'AUTO height' => ['_800xAUTO_fit_center-center_none', 800, null, true],
        'lowercase AUTO width' => ['_autox600_fit_center-center_none', null, 600, true],
        'mixed case AUTO height' => ['_800xAuTo_fit_center-center_none', 800, null, true],
        'no-upscale flag' => ['_800x600_crop_center-center_none_ns', 800, 600, false],
    ]);

    test('throws on invalid string', function () {
        ImageTransformHelper::createTransformFromString('invalid');
    })->throws(ImageTransformException::class, 'Cannot create a transform from string');

    test('matches legacy createTransformFromString provider cases', function (string $transformString, array $expected) {
        $transform = ImageTransformHelper::createTransformFromString($transformString);

        foreach ($expected as $property => $value) {
            expect($transform->{$property})->toBe($value);
        }
    })->with([
        'happy path' => [
            '_1280x600_crop_center-center',
            [
                'width' => 1280,
                'height' => 600,
                'mode' => 'crop',
                'position' => 'center-center',
            ],
        ],
        'with quality' => [
            '_1280x600_crop_center-center_95',
            ['quality' => 95],
        ],
        'with interlace' => [
            '_1280x600_crop_center-center_95_line',
            ['interlace' => 'line'],
        ],
        'with fill' => [
            '_1280x600_crop_center-center_95_line_ff0000',
            ['fill' => '#ff0000'],
        ],
        // Pattern is intentionally non-anchored; invalid fill suffix is ignored.
        'invalid fill suffix' => [
            '_1280x600_crop_center-center_95_line_invalidFill',
            ['fill' => null],
        ],
        'transparent fill' => [
            '_1280x600_crop_center-center_95_line_transparent',
            ['fill' => 'transparent'],
        ],
        'no upscale' => [
            '_1280x600_crop_center-center_95_line_ns',
            ['upscale' => false],
        ],
        'no upscale with fill' => [
            '_1280x600_crop_center-center_95_line_ff0000_ns',
            [
                'fill' => '#ff0000',
                'upscale' => false,
            ],
        ],
    ]);
});

describe('extendTransform', function () {
    test('overrides specified parameters', function () {
        $original = new ImageTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'crop',
            'quality' => 80,
        ]);

        $extended = ImageTransformHelper::extendTransform($original, [
            'width' => 400,
            'quality' => 90,
        ]);

        expect($extended->width)->toBe(400)
            ->and($extended->height)->toBe(600)
            ->and($extended->quality)->toBe(90)
            ->and($extended->mode)->toBe('crop');
    });

    test('does not modify original transform', function () {
        $original = new ImageTransform([
            'width' => 800,
            'height' => 600,
        ]);

        ImageTransformHelper::extendTransform($original, ['width' => 400]);

        expect($original->width)->toBe(800);
    });

    test('nullifies identity fields', function () {
        $original = new ImageTransform([
            'id' => 1,
            'name' => 'Test',
            'handle' => 'test',
            'uid' => 'some-uid',
            'width' => 800,
        ]);

        $extended = ImageTransformHelper::extendTransform($original, ['width' => 400]);

        expect($extended->id)->toBeNull()
            ->and($extended->name)->toBeNull()
            ->and($extended->handle)->toBeNull()
            ->and($extended->uid)->toBeNull()
            ->and($extended->parameterChangeTime)->toBeNull();
    });

    test('returns same instance when parameters are empty', function () {
        $original = new ImageTransform(['width' => 800]);

        $result = ImageTransformHelper::extendTransform($original, []);

        expect($result)->toBe($original);
    });

    test('ignores unknown parameters', function () {
        $original = new ImageTransform(['width' => 800]);

        $extended = ImageTransformHelper::extendTransform($original, [
            'width' => 400,
            'nonExistentProperty' => 'value',
        ]);

        expect($extended->width)->toBe(400);
    });

    test('matches legacy extendTransform provider cases', function (ImageTransform $transform, array $parameters, array $expected) {
        $extended = ImageTransformHelper::extendTransform($transform, $parameters);

        foreach ($expected as $property => $value) {
            expect($extended->{$property})->toBe($value);
        }
    })->with([
        'adds format without changing dimensions' => [
            new ImageTransform(['width' => 200, 'height' => 200]),
            ['format' => 'jpg'],
            ['width' => 200, 'height' => 200, 'format' => 'jpg'],
        ],
        'no-op when parameters are empty' => [
            new ImageTransform(['width' => 200, 'height' => 200]),
            [],
            ['width' => 200, 'height' => 200],
        ],
        'allows nullable width override' => [
            new ImageTransform(['width' => 200, 'height' => 200]),
            ['width' => null],
            ['width' => null, 'height' => 200],
        ],
        'nullifies handle even when override is provided' => [
            new ImageTransform(['width' => 200, 'height' => 200, 'handle' => 'square']),
            ['format' => 'jpg', 'handle' => 'rectangle'],
            ['width' => 200, 'height' => 200, 'format' => 'jpg', 'handle' => null],
        ],
        'nullifies identity fields for cloned transform' => [
            new ImageTransform([
                'width' => 200,
                'height' => 200,
                'id' => 88,
                'uid' => 'legacy-uid',
                'handle' => 'square',
                'parameterChangeTime' => new \DateTime,
            ]),
            ['format' => 'jpg'],
            [
                'width' => 200,
                'height' => 200,
                'format' => 'jpg',
                'handle' => null,
                'id' => null,
                'uid' => null,
                'parameterChangeTime' => null,
            ],
        ],
    ]);
});

describe('normalizeTransform', function () {
    test('returns null for falsy values', function (mixed $value) {
        expect(ImageTransformHelper::normalizeTransform($value))->toBeNull();
    })->with([
        'null' => [null],
        'empty string' => [''],
        'zero' => [0],
        'false' => [false],
        'true' => [true],
    ]);

    test('returns same instance for ImageTransform', function () {
        $transform = new ImageTransform(['width' => 800]);

        expect(ImageTransformHelper::normalizeTransform($transform))->toBe($transform);
    });

    test('creates transform from array', function () {
        $result = ImageTransformHelper::normalizeTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'fit',
        ]);

        expect($result)->toBeInstanceOf(ImageTransform::class)
            ->and($result->width)->toBe(800)
            ->and($result->height)->toBe(600)
            ->and($result->mode)->toBe('fit');
    });

    test('normalizes non-numeric dimensions to null', function (array $input, ?int $expectedWidth, ?int $expectedHeight) {
        $result = ImageTransformHelper::normalizeTransform($input);

        expect($result->width)->toBe($expectedWidth)
            ->and($result->height)->toBe($expectedHeight);
    })->with([
        'non-numeric width' => [['width' => 'abc', 'height' => 600], null, 600],
        'non-numeric height' => [['width' => 800, 'height' => 'abc'], 800, null],
    ]);

    test('creates transform from object', function () {
        $obj = (object) [
            'width' => 800,
            'height' => 600,
        ];

        $result = ImageTransformHelper::normalizeTransform($obj);

        expect($result)->toBeInstanceOf(ImageTransform::class)
            ->and($result->width)->toBe(800);
    });

    test('creates transform from transform string', function () {
        $result = ImageTransformHelper::normalizeTransform('_800x600_crop_center-center_none');

        expect($result)->toBeInstanceOf(ImageTransform::class)
            ->and($result->width)->toBe(800)
            ->and($result->height)->toBe(600)
            ->and($result->mode)->toBe('crop');
    });

    test('extends base transform when array has transform key', function () {
        $base = new ImageTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'crop',
            'quality' => 80,
        ]);

        $result = ImageTransformHelper::normalizeTransform([
            'transform' => $base,
            'width' => 400,
        ]);

        expect($result)->toBeInstanceOf(ImageTransform::class)
            ->and($result->width)->toBe(400)
            ->and($result->height)->toBe(600)
            ->and($result->mode)->toBe('crop');
    });

    test('throws for invalid string handle', function () {
        $this->mock(ImageTransforms::class, function ($mock) {
            $mock->shouldReceive('getTransformByHandle')
                ->with('nonExistent')
                ->andReturn(null);
        });

        ImageTransformHelper::normalizeTransform('nonExistent');
    })->throws(ImageTransformException::class, 'Invalid transform handle');

    test('looks up named transform by handle', function (string $handleInput) {
        $transform = new ImageTransform(['handle' => 'myHandle', 'width' => 500]);

        $this->mock(ImageTransforms::class, function ($mock) use ($transform) {
            $mock->shouldReceive('getTransformByHandle')
                ->with('myHandle')
                ->andReturn($transform);
        });

        $result = ImageTransformHelper::normalizeTransform($handleInput);

        expect($result)->toBe($transform);
    })->with([
        'handle' => ['myHandle'],
        'handle with underscore' => ['_myHandle'],
    ]);

    test('matches legacy normalizeTransform provider cases', function (mixed $input, ?array $expected) {
        $result = ImageTransformHelper::normalizeTransform($input);

        if ($expected === null) {
            expect($result)->toBeNull();

            return;
        }

        expect($result)->toBeInstanceOf(ImageTransform::class);

        foreach ($expected as $property => $value) {
            expect($result->{$property})->toBe($value);
        }
    })->with([
        'object input' => [
            (object) [
                'id' => 123,
                'name' => 'Test Transform',
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
            ],
            [
                'id' => 123,
                'name' => 'Test Transform',
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
            ],
        ],
        'array input' => [
            [
                'id' => 123,
                'name' => 'Test Transform',
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
            ],
            [
                'id' => 123,
                'name' => 'Test Transform',
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
            ],
        ],
        'invalid fill' => [
            ['fill' => 'invalidFill'],
            ['fill' => null],
        ],
        'transparent fill' => [
            ['fill' => 'transparent'],
            ['fill' => 'transparent'],
        ],
        'extended transform from array base' => [
            [
                'id' => 123,
                'name' => 'Test Transform',
                'handle' => 'testTransform',
                'width' => 100,
                'height' => 200,
                'mode' => 'fit',
                'position' => 'center-center',
                'fill' => '#ff0000',
                'quality' => 95,
                'interlace' => 'line',
                'upscale' => true,
                'transform' => [
                    'id' => '200',
                    'name' => 'Base Transform',
                    'width' => '300',
                    'height' => '400',
                ],
            ],
            [
                'id' => null,
                'name' => null,
                'width' => 100,
                'height' => 200,
            ],
        ],
        'valid transform string' => [
            '_1280x600_crop_center-center',
            [
                'width' => 1280,
                'height' => 600,
                'mode' => 'crop',
                'position' => 'center-center',
            ],
        ],
    ]);
});

describe('getTransformString and parseTransformString roundtrip', function () {
    test('roundtrips through getTransformString and parseTransformString', function (array $config) {
        $transform = new ImageTransform($config);
        $string = ImageTransformHelper::getTransformString($transform, true);
        $parsed = ImageTransformHelper::parseTransformString($string);

        expect($parsed['width'])->toBe($transform->width)
            ->and($parsed['height'])->toBe($transform->height)
            ->and($parsed['mode'])->toBe($transform->mode)
            ->and($parsed['position'])->toBe($transform->position)
            ->and($parsed['quality'])->toBe($transform->quality)
            ->and($parsed['interlace'])->toBe($transform->interlace)
            ->and($parsed['fill'])->toBe($transform->fill)
            ->and($parsed['upscale'])->toBe($transform->upscale);
    })->with([
        'basic crop' => [['width' => 800, 'height' => 600, 'mode' => 'crop']],
        'fit with quality' => [['width' => 400, 'height' => 300, 'mode' => 'fit', 'quality' => 85]],
        'no upscale' => [['width' => 200, 'height' => 200, 'mode' => 'crop', 'upscale' => false]],
        'letterbox with fill' => [['width' => 100, 'height' => 100, 'mode' => 'letterbox', 'fill' => '#aabbcc']],
        'width only' => [['width' => 500, 'mode' => 'fit']],
        // Legacy provider parity cases
        'legacy top-left partition' => [[
            'width' => 100,
            'height' => 200,
            'mode' => 'fit',
            'position' => 'top-left',
            'quality' => 70,
            'interlace' => 'partition',
            'fill' => null,
            'upscale' => true,
        ]],
        'legacy null height no upscale' => [[
            'width' => 100,
            'height' => null,
            'mode' => 'crop',
            'position' => 'bottom-right',
            'quality' => null,
            'interlace' => 'none',
            'fill' => null,
            'upscale' => false,
        ]],
        'legacy transparent fill' => [[
            'width' => 100,
            'height' => 200,
            'mode' => 'fit',
            'position' => 'top-left',
            'quality' => 70,
            'interlace' => 'partition',
            'fill' => 'transparent',
            'upscale' => true,
        ]],
        'legacy shorthand fill' => [[
            'width' => 100,
            'height' => 200,
            'mode' => 'fit',
            'position' => 'top-left',
            'quality' => 70,
            'interlace' => 'partition',
            'fill' => '#f00',
            'upscale' => false,
        ]],
        'legacy full hex fill' => [[
            'width' => 100,
            'height' => 200,
            'mode' => 'fit',
            'position' => 'top-left',
            'quality' => 70,
            'interlace' => 'partition',
            'fill' => '#ff0000',
            'upscale' => true,
        ]],
    ]);
});
