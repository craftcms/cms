<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\File;

test('encode', function (string $expected, mixed $data) {
    expect(Json::encode($data))->toBe($expected);
})->with([
    ['{"test":"test"}', ['test' => 'test']],
    ['{"test":"€"}', ['test' => '€']],
]);

test('decode', function (mixed $expected, mixed $str, bool $asArray) {
    expect(Json::decode($str, $asArray))->toEqualCanonicalizing($expected);
})->with([
    'object as array' => [['test' => 'test'], '{"test":"test"}', true],
    'object as object' => [(object) ['test' => 'test'], '{"test":"test"}', false],
    'array' => [['test'], '["test"]', true],
    'string' => ['test', '"test"', true],
    'integer' => [42, '42', true],
    'boolean' => [true, 'true', true],
    'JSON null' => [null, 'null', true],
    'empty string' => [null, '', true],
    'null' => [null, null, true],
]);

test('decode rejects invalid values', function (mixed $value) {
    expect(fn () => Json::decode($value))
        ->toThrow(InvalidArgumentException::class, 'Invalid JSON data.');
})->with([
    'malformed JSON' => ['{"test":"test"'],
    'non-string value' => [[]],
]);

test('decodeIfJson', function (mixed $expected, string $str) {
    expect(Json::decodeIfJson($str))->toBe($expected);
})->with(function () {
    $basicArray = [
        'WHAT DO WE WANT' => 'JSON',
        'WHEN DO WE WANT IT' => 'NOW',
    ];

    return [
        ['{"test":"test"', '{"test":"test"'],
        [$basicArray, json_encode($basicArray)],
        [null, ''],
    ];
});

test('decodeFromFile', function () {
    $path = storage_path('runtime/test.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '{"test":"test"}');

    expect(Json::decodeFromFile($path))->toEqualCanonicalizing(['test' => 'test']);

    File::delete($path);
});

test('detectIndent', function (string $expected, string $json) {
    expect(Json::detectIndent($json))->toBe($expected);
})->with([
    [' ', "{\n \"foo\": true\n}"],
    ["\t", "{\n\t\"foo\": true\n}"],
    ['  ', '{}'],
]);
