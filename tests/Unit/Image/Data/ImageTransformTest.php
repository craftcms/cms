<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageTransform;

describe('defaults', function () {
    test('has sensible defaults', function () {
        $transform = new ImageTransform;

        expect($transform->id)->toBeNull()
            ->and($transform->name)->toBeNull()
            ->and($transform->handle)->toBeNull()
            ->and($transform->driver)->toBeNull()
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
    test('returns true when id is set', function () {
        $transform = new ImageTransform(['id' => 1]);

        expect($transform->getIsNamedTransform())->toBeTrue();
    });

    test('returns false when id is null', function () {
        $transform = new ImageTransform;

        expect($transform->getIsNamedTransform())->toBeFalse();
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
            'name' => 'Thumbnail',
            'handle' => 'thumb',
            'driver' => null,
            'operations' => [
                'fill' => '#ff0000',
                'format' => 'webp',
                'height' => 200,
                'interlace' => 'none',
                'mode' => 'crop',
                'position' => 'center-center',
                'quality' => 80,
                'upscale' => true,
                'width' => 200,
            ],
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

        expect($config['operations']['width'])->toBeNull()
            ->and($config['operations']['height'])->toBeNull();
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
