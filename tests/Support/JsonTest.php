<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
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
    [['test' => 'test'], '{"test":"test"}', true],
    [(object) ['test' => 'test'], '{"test":"test"}', false],
    [null, '', true],
    [null, null, true],
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
    File::put(Aliases::get('@runtime/test.json'), '{"test":"test"}');

    expect(Json::decodeFromFile('@runtime/test.json'))->toEqualCanonicalizing(['test' => 'test']);

    File::delete(Aliases::get('@runtime/test.json'));
});

test('detectIndent', function (string $expected, string $json) {
    expect(Json::detectIndent($json))->toBe($expected);
})->with([
    [' ', "{\n \"foo\": true\n}"],
    ["\t", "{\n\t\"foo\": true\n}"],
    ['  ', '{}'],
]);
