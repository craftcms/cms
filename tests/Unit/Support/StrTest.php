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

test('containsNewlines', function (string $string, bool $expected) {
    expect(Str::containsNewlines($string))->toBe($expected);
})->with([
    ['Str contains no newlines', false],
    ['', false],
    ["Str contains a tab\tand spaces", false],
    ["Str contains a\nnewline", true],
    ["Str contains a\rcarriage return", true],
    ["Str contains a\r\nCRLF", true],
    ["\nStarts with a newline", true],
    ["Ends with a newline\n", true],
    ["Multiple\nnewlines\nhere", true],
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
    ['Café terrace', "Caf\xe9 terrace"],
    ['“Quoted”', "\x93Quoted\x94"],
]);

test('convertToUtf8 accepts explicit source encoding', function () {
    $iso88591 = iconv('UTF-8', 'ISO-8859-1//IGNORE', 'Café');

    expect(Str::convertToUtf8($iso88591, 'iso-8859-1'))->toBe('Café');
});

test('convertLineBreaks', function (string $expected, string $string) {
    expect(Str::convertLineBreaks($string))->toBe($expected);
})->with([
    ['foo bar', 'foo bar'],
    ["foo\nbar", "foo\nbar"],
    ["foo\nbar", "foo\rbar"],
    ["foo\nbar", "foo\r\nbar"],
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
    ['Baby you light my :fire:! :glad:', 'Baby you light my 🔥! 😃'],
    ['Test — em – en - dashes :fingers_crossed:', 'Test — em – en - dashes 🤞'],
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

test('lower', function (string $expected, string $string, ?string $language = null) {
    expect(Str::lower($string, $language))->toBe($expected);
})->with([
    // Turkish lowercases a dotless "I" to a dotless "ı", not "i"
    // (https://github.com/craftcms/cms/discussions/19555)
    ['ıstanbul', 'Istanbul', 'tr'],
    ['istanbul', 'Istanbul'],
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

test('title', function (string $expected, string $string, ?string $language = null) {
    expect(Str::title($string, $language))->toBe($expected);
})->with([
    // Dutch "ij" is treated as a single letter, so title-casing it capitalizes both characters
    // (https://github.com/craftcms/cms/discussions/19555)
    ['IJsselmeer', 'ijsselmeer', 'nl'],
    ['Ijsselmeer', 'ijsselmeer'],
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

test('upper', function (string $expected, string $string, ?string $language = null) {
    expect(Str::upper($string, $language))->toBe($expected);
})->with([
    // Turkish uppercases a dotted "i" to a dotted "İ", not "I"
    // (https://github.com/craftcms/cms/discussions/19555)
    ['İSTANBUL', 'istanbul', 'tr'],
    ['ISTANBUL', 'istanbul'],
    // Greek strips accents when uppercasing
    // (https://github.com/craftcms/cms/discussions/19555)
    ['ΑΝΘΡΩΠΟΣ', 'άνθρωπος', 'el'],
    ['ΆΝΘΡΩΠΟΣ', 'άνθρωπος'],
]);

test('invisibleCharsPattern', function (string $expected, string $str) {
    expect(preg_replace(Str::invisibleCharsPattern(), '', $str))->toBe($expected);
})->with([
    ['Craft CMS', "Craft\u{200B} CMS"],
    ['invoicefdp.exe', "invoice\u{202E}fdp.exe"],
    ['abc', "a\u{2066}b\u{2069}c"],
    ['ab', "a\u{2060}\u{034F}\u{180E}b"],
    ['ab', "a\u{3164}\u{115F}\u{1160}\u{FFA0}\u{2800}b"],
    ['ab', "a\x00\x07\x1B\x7Fb"],
    ['ab', "a\u{0083}\u{009F}b"],
    ["a \t\n\r\u{00A0}\u{0085}b", "a \t\n\r\u{00A0}\u{0085}b"],
    ['Ünïcödé 😀 こんにちは', 'Ünïcödé 😀 こんにちは'],
]);

test('stripPrivateAndUnassignedChars', function (string $expected, string $str) {
    expect(Str::stripPrivateAndUnassignedChars($str))->toBe($expected);
})->with([
    ['', ''],
    ['Apple', "\u{F8FF}Apple"],
    ['ab', "a\u{E000}\u{F0000}\u{10FFFD}b"],
    ['ab', "a\u{0378}b"],
    ['ab', "a\u{FFFE}b"],
    ['Ünïcödé 😀 こんにちは', 'Ünïcödé 😀 こんにちは'],
]);

test('areConfusable', function (bool $expected, string $a, string $b) {
    expect(Str::areConfusable($a, $b))->toBe($expected)
        ->and(Str::areConfusable($b, $a))->toBe($expected);
})->with([
    [true, 'craft cms', 'Craft CMS'],
    [true, "\u{FF23}\u{FF52}\u{FF41}\u{FF46}\u{FF54}", 'Craft'], // full-width
    [true, "Craft\u{200B}  \u{00A0}CMS", 'Craft CMS'], // zero-width space, extra spaces, NBSP
    [true, "\u{0421}raft", 'Craft'], // Cyrillic Es
    [true, "\u{0441}r\u{0430}ft \u{0441}ms", 'Craft CMS'], // Cyrillic es and a, lowercase
    [true, "HAPPY LAG\u{0415}R", 'Happy Lager'], // Cyrillic Ie, uppercase
    [true, "cra\u{017F}t", 'Craft'], // long s, lowercase
    [true, 'Apex', "\u{0410}\u{0440}\u{0435}\u{0445}"], // all-Cyrillic “Арех”
    [true, 'kato', "\u{039A}\u{0391}\u{03A4}\u{039F}"], // all-Greek “ΚΑΤΟ”, lowercase
    [false, 'Apex', "\u{041C}\u{043E}\u{0441}\u{043A}\u{0432}\u{0430}"], // Cyrillic “Москва”
    [false, 'Crafty', 'Craft'],
    [false, 'Cr4ft', 'Craft'],
    [false, 'CraftCMS', 'Craft CMS'],
    [false, "\u{0421}raft Companion", 'Craft'],
]);
