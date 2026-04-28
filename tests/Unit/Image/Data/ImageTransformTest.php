<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;

describe('defaults', function () {
    test('has sensible defaults', function () {
        $transform = new ImageTransform;

        expect($transform->id)->toBeNull()
            ->and($transform->name)->toBeNull()
            ->and($transform->handle)->toBeNull()
            ->and($transform->width)->toBeNull()
            ->and($transform->height)->toBeNull()
            ->and($transform->format)->toBeNull()
            ->and($transform->quality)->toBeNull()
            ->and($transform->mode)->toBe('crop')
            ->and($transform->position)->toBe('center-center')
            ->and($transform->interlace)->toBe('none')
            ->and($transform->fill)->toBeNull()
            ->and($transform->upscale)->toBeTrue()
            ->and($transform->uid)->toBeNull()
            ->and($transform->parameterChangeTime)->toBeNull();
    });

    test('accepts config array', function () {
        $transform = new ImageTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'fit',
            'quality' => 85,
        ]);

        expect($transform->width)->toBe(800)
            ->and($transform->height)->toBe(600)
            ->and($transform->mode)->toBe('fit')
            ->and($transform->quality)->toBe(85);
    });
});

describe('getIsNamedTransform', function () {
    test('returns true when id is set and transformer is default', function () {
        $transform = new ImageTransform(['id' => 1]);

        expect($transform->getIsNamedTransform())->toBeTrue();
    });

    test('returns false when id is null', function () {
        $transform = new ImageTransform;

        expect($transform->getIsNamedTransform())->toBeFalse();
    });

    test('returns false when transformer is not default', function () {
        $transform = new ImageTransform(['id' => 1]);
        $transform->setTransformer('SomeOther\Transformer');

        expect($transform->getIsNamedTransform())->toBeFalse();
    });
});

describe('modes', function () {
    test('returns all four modes', function () {
        $modes = ImageTransform::modes();

        expect($modes)->toHaveKeys(['crop', 'fit', 'stretch', 'letterbox'])
            ->and($modes)->toHaveCount(4);
    });
});

describe('transformer', function () {
    test('defaults to ImageTransformer', function () {
        $transform = new ImageTransform;

        expect($transform->getTransformer())->toBe(ImageTransformer::class);
    });

    test('can set a custom transformer', function () {
        $transform = new ImageTransform;
        $transform->setTransformer('Custom\Transformer');

        expect($transform->getTransformer())->toBe('Custom\Transformer');
    });

    test('falls back to default when set to null', function () {
        $transform = new ImageTransform;
        $transform->setTransformer('Custom\Transformer');
        $transform->setTransformer(null);

        expect($transform->getTransformer())->toBe(ImageTransformer::class);
    });
});

describe('getConfig', function () {
    test('returns project config representation', function () {
        $transform = new ImageTransform([
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'width' => 200,
            'height' => 200,
            'mode' => 'crop',
            'position' => 'center-center',
            'quality' => 80,
            'interlace' => 'none',
            'format' => 'webp',
            'fill' => '#ff0000',
            'upscale' => true,
        ]);

        expect($transform->getConfig())->toBe([
            'fill' => '#ff0000',
            'format' => 'webp',
            'handle' => 'thumb',
            'height' => 200,
            'interlace' => 'none',
            'mode' => 'crop',
            'name' => 'Thumbnail',
            'position' => 'center-center',
            'quality' => 80,
            'upscale' => true,
            'width' => 200,
        ]);
    });

    test('returns null for zero width and height', function () {
        $transform = new ImageTransform([
            'name' => 'Test',
            'handle' => 'test',
            'width' => 0,
            'height' => 0,
        ]);

        $config = $transform->getConfig();

        expect($config['width'])->toBeNull()
            ->and($config['height'])->toBeNull();
    });

    test('excludes non-config fields', function () {
        $config = new ImageTransform([
            'id' => 1,
            'uid' => 'some-uid',
            'name' => 'Test',
            'handle' => 'test',
        ])->getConfig();

        expect($config)->not->toHaveKey('id')
            ->and($config)->not->toHaveKey('uid')
            ->and($config)->not->toHaveKey('parameterChangeTime');
    });
});

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
            'format' => 'bmp',
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

describe('DEFAULT_TRANSFORMER constant', function () {
    test('points to ImageTransformer class', function () {
        expect(ImageTransform::DEFAULT_TRANSFORMER)->toBe(ImageTransformer::class);
    });
});
