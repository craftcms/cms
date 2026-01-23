<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;

describe('Integer validation', function () {
    test('integer fields accept valid values', function (string $field, int $value) {
        $asset = AssetModel::factory()->createElement();
        $asset->{$field} = $value;

        $asset->validate([$field]);

        expect($asset->hasErrors($field))->toBeFalse();
    })->with([
        'volumeId accepts 1' => ['volumeId', 1],
        'folderId accepts 1' => ['folderId', 1],
        'width accepts 1920' => ['width', 1920],
        'width accepts 0' => ['width', 0],
        'height accepts 1080' => ['height', 1080],
        'size accepts 1048576' => ['size', 1048576],
    ]);

    test('nullable integer fields accept null', function (string $field) {
        $asset = AssetModel::factory()->createElement();
        $asset->{$field} = null;

        $asset->validate([$field]);

        expect($asset->hasErrors($field))->toBeFalse();
    })->with([
        'volumeId accepts null' => ['volumeId'],
        'folderId accepts null' => ['folderId'],
        'width accepts null' => ['width'],
        'height accepts null' => ['height'],
        'size accepts null' => ['size'],
    ]);

    test('integer fields accept zero', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->width = 0;
        $asset->height = 0;
        $asset->size = 0;

        $asset->validate(['width', 'height', 'size']);

        expect($asset->hasErrors())->toBeFalse();
    });
});

describe('DateTime validation', function () {
    test('dateModified accepts valid values', function (mixed $value) {
        $asset = AssetModel::factory()->createElement();
        $asset->dateModified = $value;

        $asset->validate(['dateModified']);

        expect($asset->hasErrors('dateModified'))->toBeFalse();
    })->with([
        'DateTime object' => [new DateTime],
        'null' => [null],
        'past date' => [new DateTime('2020-01-01')],
        'future date' => [new DateTime('+1 year')],
    ]);
});

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
    test('safe attributes accept valid values', function (string $field, mixed $value) {
        $asset = AssetModel::factory()->createElement();
        $asset->{$field} = $value;

        $asset->validate([$field]);

        expect($asset->hasErrors($field))->toBeFalse();
    })->with([
        'filename accepts valid string' => ['filename', 'new-filename.jpg'],
        'newFilename accepts valid string' => ['newFilename', 'renamed-file.jpg'],
        'alt accepts string with special chars' => ['alt', 'Some alternative text with special characters: <>&"'],
        'alt accepts null' => ['alt', null],
        'alt accepts empty string' => ['alt', ''],
    ]);
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
    test('null values are handled gracefully for all nullable fields', function () {
        $asset = AssetModel::factory()->createElement();
        $asset->volumeId = null;
        $asset->folderId = null;
        $asset->width = null;
        $asset->height = null;
        $asset->size = null;
        $asset->dateModified = null;
        $asset->alt = null;

        $asset->validate(['volumeId', 'folderId', 'width', 'height', 'size', 'dateModified', 'alt']);

        expect($asset->hasErrors('volumeId'))->toBeFalse();
        expect($asset->hasErrors('folderId'))->toBeFalse();
        expect($asset->hasErrors('width'))->toBeFalse();
        expect($asset->hasErrors('height'))->toBeFalse();
        expect($asset->hasErrors('size'))->toBeFalse();
        expect($asset->hasErrors('dateModified'))->toBeFalse();
        expect($asset->hasErrors('alt'))->toBeFalse();
    });

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
