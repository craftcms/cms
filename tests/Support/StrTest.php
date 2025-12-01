<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Stringable;

test('asciiCharMap', function (string $char, string $ascii) {
    $mapByAscii = Str::asciiCharMap(false, 'de');
    expect($mapByAscii)->toHaveKey($ascii);
    expect($mapByAscii[$ascii])->toContain($char);

    $mapByChar = Str::asciiCharMap(true, 'de');
    expect($mapByChar)->toHaveKey($char);
    expect($mapByChar[$char])->toBe($ascii);
})->with([
    ['ä', 'ae'],
    ['ö', 'oe'],
    ['ü', 'ue'],
    ['Ä', 'Ae'],
    ['Ö', 'Oe'],
    ['Ü', 'Ue'],
    ['é', 'e'],
]);

test('containsMb4', function (string $string, bool $expected) {
    expect(Str::containsMb4($string))->toBe($expected);
})->with([
    ['😀😘', true],
    ['QWERTYUIOPASDFGHJKLZXCVBNM1234567890😘', true],
    ['!@#$%^&*()_🎧', true],
    ['!@#$%^&*(𢵌)_', true],
    ['QWERTYUIOPASDFGHJKLZXCVBNM1234567890', false],
    ['!@#$%^&*()_', false],
    ['⛄', false],
    ['', false],
    ['𨳊', true],
]);

test('convertToUtf8', function (string $expected, string $string) {
    expect(Str::convertToUtf8($string))->toBe($expected);
})->with([
    ['κόσμε', 'κόσμε'],
    ['\x74\x65\x73\x74', '\x74\x65\x73\x74'],
    ['craftcms', 'craftcms'],
    ['😂😁', '😂😁'],
    ['Foo © bar 𝌆 baz ☃ qux', 'Foo © bar 𝌆 baz ☃ qux'],
    ['İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±', 'İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±'],
]);

test('encDec', function (string $string) {
    $enc = Str::encenc($string);
    expect($enc)->toStartWith('base64:');
    expect(Str::decdec($enc))->toBe($string);
})->with([
    ['1234567890asdfghjkl'],
    ['😂😁'],
    ['!@#$%^&*()_+{}|:"<>?'],
]);

test('emojiToShortcodes', function (string $expected, string $str) {
    expect(Str::emojiToShortcodes($str))->toBe($expected);
})->with([
    ['Baby you light my :fire:! :smiley:', 'Baby you light my 🔥! 😃'],
    ['Test — em – en - dashes :hand_with_index_and_middle_fingers_crossed:', 'Test — em – en - dashes 🤞'],
]);

test('encodeMb4', function (string $expected, string $string) {
    $actual = Str::encodeMb4($string);
    expect($actual)->toBe($expected);
    expect(Str::containsMb4($actual))->toBeFalse();
})->with([
    ['&#x1f525;', '🔥'],
    ['&#x1f525;', '&#x1f525;'],
    ['&#x1f1e6;&#x1f1fa;', '🇦🇺'],
    ['&#x102cd;', '𐋍'],
    ['asdfghjklqwertyuiop1234567890!@#$%^&*()_+', 'asdfghjklqwertyuiop1234567890!@#$%^&*()_+'],
    ['&#x102cd;&#x1f1e6;&#x1f1fa;&#x1f525;', '𐋍🇦🇺🔥'],
    'ensure-non-mb4-is-ignored' => ['&#x102cd;1234567890&#x1f1e6;&#x1f1fa; &#x1f525;', '𐋍1234567890🇦🇺 🔥'],
]);

test('escapeShortcodes', function (string $expected, string $str) {
    expect(Str::escapeShortcodes($str))->toBe($expected);
})->with([
    ['\\:100\\: \\:1234\\: 🔥', ':100: :1234: 🔥'],
]);

test('firstLine', function (string $expected, string $string) {
    expect(Str::firstLine($string))->toBe($expected);
})->with([
    [
        'test',
        'test


             test',
    ],
    ['test <br> test', 'test <br> test'],
    ['thesearetabs       notspaces', 'thesearetabs       notspaces'],
    [
        '😂', '😂
            😁',
    ],
    [
        '', '









            ',
    ],
]);

test('idnToUtf8Email', function (string $expected, string $string) {
    expect(Str::idnToUtf8Email($string))->toBe($expected);
})->with([
    ['userName', 'userName'],
    ['aaa@äö.ee', 'aaa@xn--4ca0b.ee'],
]);

test('insert', function (string $expected, string $string, string $substring, int $index) {
    expect(Str::insert($string, $substring, $index))->toBe($expected);
})->with([
    ['foo bar', 'oo bar', 'f', 0],
    ['foo bar', 'f bar', 'oo', 1],
    ['f bar', 'f bar', 'oo', 20],
    ['foo bar', 'foo ba', 'r', 6],
    ['fòôbàř', 'fòôbř', 'à', 4],
    ['fòô bàř', 'òô bàř', 'f', 0],
    ['fòô bàř', 'f bàř', 'òô', 1],
    ['fòô bàř', 'fòô bà', 'ř', 6],
]);

test('isHexadecimal', function (bool $expected, string $string) {
    expect(Str::isHexadecimal($string))->toBe($expected);
})->with([
    [true, ''],
    [true, 'abcdef'],
    [true, 'ABCDEF'],
    [true, '0123456789'],
    [true, '0123456789AbCdEf'],
    [false, '0123456789x'],
    [false, 'ABCDEFx'],
    [true, 'abcdef'],
    [true, 'ABCDEF'],
    [true, '0123456789'],
    [true, '0123456789AbCdEf'],
    [false, '0123456789x'],
    [false, 'ABCDEFx'],
]);

test('lines', function (int $expected, string $string) {
    expect(Str::lines($string))->toHaveCount($expected);
})->with([
    [
        4, 'test
             .
             .
             test',
    ],
    [1, 'test <br> test'],
    [1, 'thesearetabs       notspaces'],
    [
        2, '😂
            😁',
    ],
    [
        11, '
            .
            .
            .
            .
            .
            .
            .
            .
            .
            ',
    ],
]);

test('random', function (int $length = 36, bool $extendedChars = false) {
    $random = Str::random($length, $extendedChars);
    $len = strlen($random);

    expect($len)->toBe($length);

    $validChars = $extendedChars
        ? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"'
        : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    foreach (str_split($random) as $char) {
        expect($validChars)->toContain($char);
    }
})->with([
    [],
    [50, false],
    [55, true],
]);

test('shortcodesToEmoji', function (string $expected, string $str) {
    expect(Str::shortcodesToEmoji($str))->toBe($expected);
})->with([
    ['Baby you light my 🔥! 😃', 'Baby you light my :fire:! :smiley:'],
    ['Test — em – en - dashes 🤞', 'Test — em – en - dashes :hand_with_index_and_middle_fingers_crossed:'],
]);

test('toHandle', function (string $expected, string $str) {
    expect(Str::toHandle($str))->toBe($expected);
})->with([
    ['foo', 'FOO'],
    ['fooBar', 'FOO BAR'],
    ['fooBar', 'Fo’o Bar'],
    ['fooBarBaz', 'Foo Ba’r   Baz'],
    ['fooBar', '0 Foo Bar'],
    ['fooBar', 'Foo!Bar'],
    ['fooBar', 'Foo,Bar'],
    ['fooBar', 'Foo/Bar'],
    ['fooBar', 'Foo\\Bar'],
]);

test('toString', function (string $expected, mixed $object, string $glue = ',') {
    $actual = Str::toString($object, $glue);

    expect($actual)->toBe($expected);
})->with([
    ['test', 'test'],
    ['', new stdClass],
    ['ima string', new Stringable('ima string')],
    ['t,e,s,t', ['t', 'e', 's', 't']],
    ['t|e|s|t', ['t', 'e', 's', 't'], '|'],
    ['valid', LicenseKeyStatus::Valid],
]);

test('unescapeShortcodes', function (string $expected, string $str) {
    expect(Str::unescapeShortcodes($str))->toBe($expected);
})->with([
    [':100: :1234: 🔥', '\\:100\\: \\:1234\\: 🔥'],
]);

test('uuidPattern', function () {
    expect(Str::uuidPattern())->not()->toBeEmpty();
});
