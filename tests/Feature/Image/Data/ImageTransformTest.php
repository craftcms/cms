<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;

describe('validation', function () {
    test('validates a complete transform', function () {
        $transform = new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
            'height' => 200,
            'mode' => 'crop',
            'position' => 'center-center',
            'interlace' => 'none',
        ]);

        expect($transform->validate())->toBeTrue();
    });

    test('fails without name', function () {
        $transform = new ImageTransform([
            'handle' => 'thumb',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('name'))->toBeTrue();
    });

    test('fails without handle', function () {
        $transform = new ImageTransform([
            'name' => 'Thumb',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('handle'))->toBeTrue();
    });

    it('requires valid handles', function (string $handle, bool $expected) {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => $handle,
        ]);

        expect($transform->validate())->toBe($expected);
    })->with([
        'camel case' => ['validHandle', true],
        'underscore' => ['valid_handle', true],
        'hyphen' => ['invalid-handle', false],
        'leading number' => ['1invalid', false],
        'reserved word' => ['handle', false],
    ]);

    test('fails with duplicate handle', function () {
        $original = new ImageTransform([
            'name' => 'Original',
            'handle' => 'thumb',
            'width' => 100,
        ]);

        expect(app(ImageTransforms::class)->saveTransform($original))->toBeTrue();

        $duplicate = new ImageTransform([
            'name' => 'Duplicate',
            'handle' => 'thumb',
            'width' => 200,
        ]);

        expect($duplicate->validate())->toBeFalse()
            ->and($duplicate->errors()->has('handle'))->toBeTrue();
    });

    test('fails with invalid mode', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'mode' => 'invalid',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('mode'))->toBeTrue();
    });

    test('fails with invalid position', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'position' => 'invalid',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('position'))->toBeTrue();
    });

    test('fails with invalid interlace', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'interlace' => 'invalid',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('interlace'))->toBeTrue();
    });

    test('fails with quality out of range', function (int $quality) {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'quality' => $quality,
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('quality'))->toBeTrue();
    })->with([
        'zero' => [0],
        'over 100' => [101],
        'negative' => [-1],
    ]);

    test('accepts valid quality values', function (int $quality) {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'quality' => $quality,
        ]);

        expect($transform->validate())->toBeTrue();
    })->with([
        'min' => [1],
        'max' => [100],
        'mid' => [50],
    ]);

    test('fails with invalid format', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'format' => 'pdf',
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('format'))->toBeTrue();
    });

    test('accepts valid formats', function (string $format) {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'format' => $format,
        ]);

        expect($transform->validate())->toBeTrue();
    })->with([
        'jpg' => ['jpg'],
        'gif' => ['gif'],
        'png' => ['png'],
        'webp' => ['webp'],
        'avif' => ['avif'],
        'bmp' => ['bmp'],
        'heic' => ['heic'],
        'ico' => ['ico'],
        'jp2' => ['jp2'],
        'jxl' => ['jxl'],
        'tiff' => ['tiff'],
    ]);

    test('accepts null format', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'format' => null,
        ]);

        expect($transform->validate())->toBeTrue();
    });

    test('fails with zero width', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'width' => 0,
        ]);

        expect($transform->validate())->toBeFalse()
            ->and($transform->errors()->has('width'))->toBeTrue();
    });

    test('accepts valid positions', function (string $position) {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'position' => $position,
        ]);

        expect($transform->validate())->toBeTrue();
    })->with([
        'top-left' => ['top-left'],
        'top-center' => ['top-center'],
        'top-right' => ['top-right'],
        'center-left' => ['center-left'],
        'center-center' => ['center-center'],
        'center-right' => ['center-right'],
        'bottom-left' => ['bottom-left'],
        'bottom-center' => ['bottom-center'],
        'bottom-right' => ['bottom-right'],
    ]);
});
