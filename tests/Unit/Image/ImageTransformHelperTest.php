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

    test('uses AUTO for missing dimensions', function () {
        $transform = new ImageTransform([
            'width' => 300,
            'mode' => 'fit',
        ]);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_300xAUTO_fit_center-center_none');
    });

    test('uses AUTO for both missing dimensions', function () {
        $transform = new ImageTransform(['mode' => 'crop']);

        $result = ImageTransformHelper::getTransformString($transform, true);

        expect($result)->toBe('_AUTOxAUTO_crop_center-center_none');
    });

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
});

describe('parseTransformString', function () {
    test('parses a basic transform string', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_crop_center-center_none');

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
    });

    test('parses with leading underscore', function () {
        $result = ImageTransformHelper::parseTransformString('_800x600_crop_center-center_none');

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
    });

    test('parses AUTO as null', function () {
        $result = ImageTransformHelper::parseTransformString('AUTOx600_fit_center-center_none');

        expect($result['width'])->toBeNull()
            ->and($result['height'])->toBe(600);
    });

    test('parses quality', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_crop_center-center_80_none');

        expect($result['quality'])->toBe(80);
    });

    test('parses fill color', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_letterbox_center-center_none_ff0000');

        expect($result['fill'])->toBe('#ff0000');
    });

    test('parses transparent fill', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_letterbox_center-center_none_transparent');

        expect($result['fill'])->toBe('transparent');
    });

    test('parses no-upscale flag', function () {
        $result = ImageTransformHelper::parseTransformString('800x600_crop_center-center_none_ns');

        expect($result['upscale'])->toBeFalse();
    });

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

    test('handles AUTO width', function () {
        $transform = ImageTransformHelper::createTransformFromString('_AUTOx600_fit_center-center_none');

        expect($transform->width)->toBeNull()
            ->and($transform->height)->toBe(600);
    });

    test('handles AUTO height', function () {
        $transform = ImageTransformHelper::createTransformFromString('_800xAUTO_fit_center-center_none');

        expect($transform->width)->toBe(800)
            ->and($transform->height)->toBeNull();
    });

    test('handles no-upscale flag', function () {
        $transform = ImageTransformHelper::createTransformFromString('_800x600_crop_center-center_none_ns');

        expect($transform->upscale)->toBeFalse();
    });

    test('throws on invalid string', function () {
        ImageTransformHelper::createTransformFromString('invalid');
    })->throws(ImageTransformException::class, 'Cannot create a transform from string');
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
});

describe('normalizeTransform', function () {
    test('returns null for falsy values', function (mixed $value) {
        expect(ImageTransformHelper::normalizeTransform($value))->toBeNull();
    })->with([
        'null' => [null],
        'empty string' => [''],
        'zero' => [0],
        'false' => [false],
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

    test('normalizes non-numeric width to null', function () {
        $result = ImageTransformHelper::normalizeTransform([
            'width' => 'abc',
            'height' => 600,
        ]);

        expect($result->width)->toBeNull()
            ->and($result->height)->toBe(600);
    });

    test('normalizes non-numeric height to null', function () {
        $result = ImageTransformHelper::normalizeTransform([
            'width' => 800,
            'height' => 'abc',
        ]);

        expect($result->width)->toBe(800)
            ->and($result->height)->toBeNull();
    });

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

    test('looks up named transform by handle', function () {
        $transform = new ImageTransform(['handle' => 'myHandle', 'width' => 500]);

        $this->mock(ImageTransforms::class, function ($mock) use ($transform) {
            $mock->shouldReceive('getTransformByHandle')
                ->with('myHandle')
                ->andReturn($transform);
        });

        $result = ImageTransformHelper::normalizeTransform('myHandle');

        expect($result)->toBe($transform);
    });

    test('strips leading underscore from handle', function () {
        $transform = new ImageTransform(['handle' => 'myHandle', 'width' => 500]);

        $this->mock(ImageTransforms::class, function ($mock) use ($transform) {
            $mock->shouldReceive('getTransformByHandle')
                ->with('myHandle')
                ->andReturn($transform);
        });

        $result = ImageTransformHelper::normalizeTransform('_myHandle');

        expect($result)->toBe($transform);
    });
});

describe('getTransformString and parseTransformString roundtrip', function () {
    test('roundtrips through getTransformString and parseTransformString', function (array $config) {
        $transform = new ImageTransform($config);
        $string = ImageTransformHelper::getTransformString($transform, true);
        $parsed = ImageTransformHelper::parseTransformString($string);

        expect($parsed['width'])->toBe($transform->width)
            ->and($parsed['height'])->toBe($transform->height)
            ->and($parsed['mode'])->toBe($transform->mode)
            ->and($parsed['interlace'])->toBe($transform->interlace)
            ->and($parsed['upscale'])->toBe($transform->upscale);
    })->with([
        'basic crop' => [['width' => 800, 'height' => 600, 'mode' => 'crop']],
        'fit with quality' => [['width' => 400, 'height' => 300, 'mode' => 'fit', 'quality' => 85]],
        'no upscale' => [['width' => 200, 'height' => 200, 'mode' => 'crop', 'upscale' => false]],
        'letterbox with fill' => [['width' => 100, 'height' => 100, 'mode' => 'letterbox', 'fill' => '#aabbcc']],
        'width only' => [['width' => 500, 'mode' => 'fit']],
    ]);
});
