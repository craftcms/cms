<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;

describe('Required field validation', function () {
    test('filename validation', function (string $value, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->filename = $value;

        $asset->validate(['filename']);

        expect($asset->hasErrors('filename'))->toBe($expectError);
    })->with([
        'empty string is invalid' => ['', true],
        'valid filename is valid' => ['valid-file.jpg', false],
    ]);

    test('kind validation', function (mixed $value, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->kind = $value;

        $asset->validate(['kind']);

        expect($asset->hasErrors('kind'))->toBe($expectError);
    })->with([
        'null is invalid' => [null, true],
        'empty string is invalid' => ['', true],
        'KIND_IMAGE is valid' => [Asset::KIND_IMAGE, false],
    ]);
});

describe('String length validation', function () {
    test('kind length validation', function (int $length, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->kind = str_repeat('a', $length);

        $asset->validate(['kind']);

        expect($asset->hasErrors('kind'))->toBe($expectError);
    })->with([
        '50 chars is valid' => [50, false],
        '51 chars is invalid' => [51, true],
    ]);
});

describe('Title validation on SCENARIO_CREATE', function () {
    test('title length validation on create scenario', function (int $length, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->title = str_repeat('a', $length);
        $asset->setScenario(Asset::SCENARIO_CREATE);

        $asset->validate(['title']);

        expect($asset->hasErrors('title'))->toBe($expectError);
    })->with([
        '255 chars is valid' => [255, false],
        '256 chars is invalid' => [256, true],
    ]);
});

describe('Safe attribute validation', function () {
    test('alt accepts string with special chars', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->alt = 'Some alternative text with special characters: <>&"';

        $asset->validate(['alt']);

        expect($asset->hasErrors('alt'))->toBeFalse();
    });
});

describe('Scenario-specific required validation', function () {
    test('newLocation is required on specific scenarios', function (string $scenario, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->setScenario($scenario);
        $asset->newLocation = null;

        $asset->validate(['newLocation']);

        expect($asset->hasErrors('newLocation'))->toBe($expectError);
    })->with([
        'SCENARIO_CREATE requires newLocation' => [Asset::SCENARIO_CREATE, true],
        'SCENARIO_MOVE requires newLocation' => [Asset::SCENARIO_MOVE, true],
        'SCENARIO_FILEOPS requires newLocation' => [Asset::SCENARIO_FILEOPS, true],
        'default scenario does not require newLocation' => [Asset::SCENARIO_DEFAULT, false],
    ]);

    test('tempFilePath is required on specific scenarios', function (string $scenario, bool $expectError) {
        $asset = AssetModel::factory()->createElement();
        $asset->setScenario($scenario);
        $asset->tempFilePath = null;

        $asset->validate(['tempFilePath']);

        expect($asset->hasErrors('tempFilePath'))->toBe($expectError);
    })->with([
        'SCENARIO_CREATE requires tempFilePath' => [Asset::SCENARIO_CREATE, true],
        'SCENARIO_REPLACE requires tempFilePath' => [Asset::SCENARIO_REPLACE, true],
        'default scenario does not require tempFilePath' => [Asset::SCENARIO_DEFAULT, false],
        'SCENARIO_MOVE does not require tempFilePath' => [Asset::SCENARIO_MOVE, false],
    ]);
});

describe('SCENARIO_INDEX validation', function () {
    test('SCENARIO_INDEX has empty validation attributes', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->setScenario(Asset::SCENARIO_INDEX);

        $activeAttributes = $asset->activeAttributes();

        expect($activeAttributes)->toBe([]);
    });

    test('validation passes with invalid values on SCENARIO_INDEX', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->kind = '';
        $asset->filename = '';
        $asset->setScenario(Asset::SCENARIO_INDEX);

        $asset->validate();

        expect($asset->hasErrors())->toBeFalse();
    });
});

describe('Edge cases', function () {
    test('unicode characters are handled in alt text', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->alt = 'Image of 山 mountain';

        $asset->validate(['alt']);

        expect($asset->hasErrors('alt'))->toBeFalse();
    });

    test('special characters in filenames', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->filename = 'my-image_2024.01.jpg';

        $asset->validate(['filename']);

        expect($asset->hasErrors('filename'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->filename = '';
        $asset->kind = '';

        $asset->validate(['filename', 'kind']);

        expect($asset->hasErrors('filename'))->toBeTrue();
        expect($asset->hasErrors('kind'))->toBeTrue();
    });

    test('all valid asset kinds are accepted', function (string $kind) {
        $asset = AssetModel::factory()->createElement();
        $asset->kind = $kind;

        $asset->validate(['kind']);

        expect($asset->hasErrors('kind'))->toBeFalse();
    })->with([
        'KIND_ACCESS' => [Asset::KIND_ACCESS],
        'KIND_AUDIO' => [Asset::KIND_AUDIO],
        'KIND_CAPTIONS_SUBTITLES' => [Asset::KIND_CAPTIONS_SUBTITLES],
        'KIND_COMPRESSED' => [Asset::KIND_COMPRESSED],
        'KIND_EXCEL' => [Asset::KIND_EXCEL],
        'KIND_FLASH' => [Asset::KIND_FLASH],
        'KIND_HTML' => [Asset::KIND_HTML],
        'KIND_ILLUSTRATOR' => [Asset::KIND_ILLUSTRATOR],
        'KIND_IMAGE' => [Asset::KIND_IMAGE],
        'KIND_JAVASCRIPT' => [Asset::KIND_JAVASCRIPT],
        'KIND_JSON' => [Asset::KIND_JSON],
        'KIND_PDF' => [Asset::KIND_PDF],
        'KIND_PHOTOSHOP' => [Asset::KIND_PHOTOSHOP],
        'KIND_PHP' => [Asset::KIND_PHP],
        'KIND_POWERPOINT' => [Asset::KIND_POWERPOINT],
        'KIND_TEXT' => [Asset::KIND_TEXT],
        'KIND_VIDEO' => [Asset::KIND_VIDEO],
        'KIND_WORD' => [Asset::KIND_WORD],
        'KIND_XML' => [Asset::KIND_XML],
        'KIND_UNKNOWN' => [Asset::KIND_UNKNOWN],
    ]);
});
