<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;

describe('defaults', function () {
    test('has sensible defaults', function () {
        $index = new ImageTransformIndex;

        expect($index->id)->toBeNull()
            ->and($index->assetId)->toBeNull()
            ->and($index->transformer)->toBeNull()
            ->and($index->filename)->toBeNull()
            ->and($index->format)->toBeNull()
            ->and($index->transformString)->toBeNull()
            ->and($index->fileExists)->toBeFalse()
            ->and($index->inProgress)->toBeFalse()
            ->and($index->error)->toBeFalse()
            ->and($index->dateIndexed)->toBeNull()
            ->and($index->dateUpdated)->toBeNull()
            ->and($index->dateCreated)->toBeNull()
            ->and($index->detectedFormat)->toBeNull();
    });

    test('accepts config array', function () {
        $index = new ImageTransformIndex([
            'assetId' => 42,
            'filename' => 'photo.jpg',
            'format' => 'webp',
            'transformString' => '_800x600_crop_center-center_none',
            'fileExists' => true,
        ]);

        expect($index->assetId)->toBe(42)
            ->and($index->filename)->toBe('photo.jpg')
            ->and($index->format)->toBe('webp')
            ->and($index->transformString)->toBe('_800x600_crop_center-center_none')
            ->and($index->fileExists)->toBeTrue();
    });
});

describe('stale inProgress reset', function () {
    test('resets inProgress when dateUpdated is more than 30 seconds ago', function () {
        $staleDate = new DateTime;
        $staleDate->modify('-60 seconds');

        $index = new ImageTransformIndex([
            'inProgress' => true,
            'dateUpdated' => $staleDate,
        ]);

        expect($index->inProgress)->toBeFalse();
    });

    test('keeps inProgress when dateUpdated is within 30 seconds', function () {
        $recentDate = new DateTime;
        $recentDate->modify('-10 seconds');

        $index = new ImageTransformIndex([
            'inProgress' => true,
            'dateUpdated' => $recentDate,
        ]);

        expect($index->inProgress)->toBeTrue();
    });

    test('keeps inProgress false when already false', function () {
        $staleDate = new DateTime;
        $staleDate->modify('-60 seconds');

        $index = new ImageTransformIndex([
            'inProgress' => false,
            'dateUpdated' => $staleDate,
        ]);

        expect($index->inProgress)->toBeFalse();
    });

    test('keeps inProgress when dateUpdated is null', function () {
        $index = new ImageTransformIndex([
            'inProgress' => true,
            'dateUpdated' => null,
        ]);

        expect($index->inProgress)->toBeTrue();
    });

    test('resets inProgress at exactly 31 seconds', function () {
        $date = new DateTime;
        $date->modify('-31 seconds');

        $index = new ImageTransformIndex([
            'inProgress' => true,
            'dateUpdated' => $date,
        ]);

        expect($index->inProgress)->toBeFalse();
    });
});

describe('getTransform and setTransform', function () {
    test('setTransform stores and getTransform retrieves', function () {
        $transform = new ImageTransform([
            'width' => 800,
            'height' => 600,
            'mode' => 'crop',
        ]);

        $index = new ImageTransformIndex;
        $index->setTransform($transform);

        expect($index->getTransform())->toBe($transform);
    });

    test('getTransform normalizes from transformString', function () {
        $index = new ImageTransformIndex([
            'transformString' => '_800x600_crop_center-center_none',
        ]);

        $transform = $index->getTransform();

        expect($transform)->toBeInstanceOf(ImageTransform::class)
            ->and($transform->width)->toBe(800)
            ->and($transform->height)->toBe(600)
            ->and($transform->mode)->toBe('crop');
    });

    test('getTransform memoizes result', function () {
        $index = new ImageTransformIndex([
            'transformString' => '_800x600_crop_center-center_none',
        ]);

        $first = $index->getTransform();
        $second = $index->getTransform();

        expect($first)->toBe($second);
    });

    test('setTransform overrides memoized value', function () {
        $index = new ImageTransformIndex([
            'transformString' => '_800x600_crop_center-center_none',
        ]);

        $index->getTransform();

        $newTransform = new ImageTransform(['width' => 400]);
        $index->setTransform($newTransform);

        expect($index->getTransform())->toBe($newTransform);
    });
});
