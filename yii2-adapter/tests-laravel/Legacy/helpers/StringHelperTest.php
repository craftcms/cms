<?php

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use craft\helpers\StringHelper;
use craft\test\mockclasses\ToString;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Str;
use Orchestra\Testbench\PHPUnit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class StringHelperTest extends TestCase
{
    public function test_ascii_char_map(): void
    {
        $expected = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            'é' => 'e',
        ];

        $mapByAscii = Str::asciiCharMap(false, 'de');
        foreach ($expected as $char => $ascii) {
            self::assertArrayHasKey($ascii, $mapByAscii);
            self::assertContains($char, $mapByAscii[$ascii]);
        }

        $mapByChar = Str::asciiCharMap(true, 'de');
        foreach ($expected as $char => $ascii) {
            self::assertArrayHasKey($char, $mapByChar);
            self::assertSame($ascii, $mapByChar[$char]);
        }
    }

    #[DataProvider('afterFirstDataProvider')]
    public function test_after_first(string $expected, string $string, string $separator, bool $caseSensitive = true): void
    {
        $actual = StringHelper::afterFirst($string, $separator, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('afterLastDataProvider')]
    public function test_after_last(string $expected, string $string, string $separator, bool $caseSensitive = true): void
    {
        $actual = StringHelper::afterLast($string, $separator, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('appendDataProvider')]
    public function test_append(string $expected, string $string, string $append): void
    {
        $actual = StringHelper::append($string, $append);
        self::assertSame($expected, $actual);
    }

    public function test_append_random_string(): void
    {
        $testArray = [
            'abc' => [1, 1],
            'öäü' => [10, 10],
            '' => [10, 0],
            ' ' => [10, 10],
            'κόσμε-öäü' => [10, 10],
        ];

        foreach ($testArray as $possibleChars => $testResult) {
            $actual = StringHelper::appendRandomString('', $testResult[0], $possibleChars);
            self::assertSame($testResult[1], StringHelper::length($actual));
        }
    }

    public function test_append_unique_identifier(): void
    {
        $uniqueIds = [];
        for ($i = 0; $i <= 100; $i++) {
            $uniqueIds[] = StringHelper::appendUniqueIdentifier('');
        }

        // detect duplicate values in the array
        foreach (array_count_values($uniqueIds) as $count) {
            self::assertSame(1, $count);
        }

        // check the string length
        foreach ($uniqueIds as $uniqueId) {
            self::assertSame(32, strlen($uniqueId));
        }
    }

    #[DataProvider('atDataProvider')]
    public function test_at(string $expected, string $string, int $position): void
    {
        $actual = StringHelper::at($string, $position);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('beforeFirstDataProvider')]
    public function test_before_first(string $expected, string $string, string $separator, bool $caseSensitive = true): void
    {
        $actual = StringHelper::beforeFirst($string, $separator, $caseSensitive);
        self::assertSame($expected, $actual);
        self::assertSame($expected, StringHelper::substringOf($string, 'b', true, $caseSensitive));
    }

    #[DataProvider('beforeLastDataProvider')]
    public function test_before_last(string $expected, string $string, string $separator, bool $caseSensitive = true): void
    {
        $actual = StringHelper::beforeLast($string, $separator, $caseSensitive);
        self::assertSame($expected, $actual);
        self::assertSame($expected, StringHelper::lastSubstringOf($string, 'b', true, $caseSensitive));
    }

    #[DataProvider('betweenDataProvider')]
    public function test_between(string $expected, string $string, string $firstChar, string $secondChar, ?int $offset = null): void
    {
        $actual = StringHelper::between($string, $firstChar, $secondChar, $offset);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('camelCaseDataProvider')]
    public function test_camel_case(string $expected, string $string): void
    {
        $actual = StringHelper::camelCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('charsAsArrayDataProvider')]
    public function test_chars_as_array(array $expected, string $string): void
    {
        $actual = StringHelper::charsAsArray($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('containsAllDataProvider')]
    public function test_contains_all(bool $expected, string $haystack, array $needles, bool $caseSensitive = true): void
    {
        $actual = StringHelper::containsAll($haystack, $needles, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('containsAnyDataProvider')]
    public function test_contains_any(bool $expected, string $haystack, array $needles, bool $caseSensitive = true): void
    {
        $actual = StringHelper::containsAny($haystack, $needles, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('convertToUtf8DataProvider')]
    public function test_convert_to_utf8(string $expected, string $string): void
    {
        $actual = StringHelper::convertToUtf8($string);
        self::assertSame($expected, $actual);
    }

    public function test_count(): void
    {
        $actual = StringHelper::count('Fòô');
        self::assertSame(3, $actual);
    }

    #[DataProvider('dasherizeDataProvider')]
    public function test_dasherize(string $expected, string $string): void
    {
        $actual = StringHelper::dasherize($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('delimitDataProvider')]
    public function test_delimit(string $expected, string $string, string $delimiter): void
    {
        $actual = StringHelper::delimit($string, $delimiter);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('endsWithDataProvider')]
    public function test_ends_with(bool $expected, string $haystack, string $needle): void
    {
        $actual = StringHelper::endsWith($haystack, $needle);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('endsWithAnyDataProvider')]
    public function test_ends_with_any(bool $expected, string $haystack, array $needles, bool $caseSensitive = true): void
    {
        $actual = StringHelper::endsWithAny($haystack, $needles, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('ensureLeftDataProvider')]
    public function test_ensure_left(string $expected, string $string, string $prepend): void
    {
        $actual = StringHelper::ensureLeft($string, $prepend);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('ensureRightDataProvider')]
    public function test_ensure_right(string $expected, string $string, string $append): void
    {
        $actual = Str::finish($string, $append);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('escapeDataProvider')]
    public function test_escape(string $expected, string $string): void
    {
        $actual = StringHelper::escape($string);
        self::assertSame($expected, $actual);
    }

    public function test_extract_text(): void
    {
        $testArray = [
            '' => '',
            '<h1>test</h1>' => '<h1>test</h1>',
            'test' => 'test',
            'A PHP string manipulation library with multibyte support. Compatible with PHP PHP 7+.' => 'A PHP string manipulation library with multibyte…',
            'A PHP string manipulation library with multibyte support. κόσμε-öäü κόσμε-öäü κόσμε-öäü foobar Compatible with PHP 7+.' => 'A PHP string manipulation library with multibyte support. κόσμε-öäü…',
            'A PHP string manipulation library with multibyte support. foobar Compatible with PHP 7+.' => 'A PHP string manipulation library with multibyte…',
        ];

        foreach ($testArray as $testString => $testExpected) {
            self::assertSame($testExpected, StringHelper::extractText($testString), 'tested: ' . $testString);
        }

        // ----------------

        $testString = 'this is only a Fork of Stringy';
        self::assertSame('…a Fork of Stringy', StringHelper::extractText($testString, 'Fork', 5), 'tested: ' . $testString);

        // ----------------

        $testString = 'This is only a Fork of Stringy, take a look at the new features.';
        self::assertSame('…Fork of Stringy…', StringHelper::extractText($testString, 'Stringy', 15), 'tested: ' . $testString);

        // ----------------

        $testString = 'This is only a Fork of Stringy, take a look at the new features.';
        self::assertSame('…only a Fork of Stringy, take a…', StringHelper::extractText($testString, 'Stringy'), 'tested: ' . $testString);

        // ----------------

        $testString = 'This is only a Fork of Stringy, take a look at the new features.';
        self::assertSame('This is only a Fork of Stringy…', StringHelper::extractText($testString), 'tested: ' . $testString);

        // ----------------

        $testString = 'This is only a Fork of Stringy, take a look at the new features.';
        self::assertSame('This…', StringHelper::extractText($testString, '', 0), 'tested: ' . $testString);

        // ----------------

        $testString = 'This is only a Fork of Stringy, take a look at the new features.';
        self::assertSame('…Stringy, take a look at the new features.', StringHelper::extractText($testString, 'Stringy', 0), 'tested: ' . $testString);

        // ----------------

        $testArray = [
            'Yes. The bird is flying in the wind. The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…The fox is jumping in the <strong>garden</strong> when he is happy. But that…',
            'The bird is flying in the wind. The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…The fox is jumping in the <strong>garden</strong> when he is happy. But that…',
            'The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…is jumping in the <strong>garden</strong> when he is happy…',
            'Yes. The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…fox is jumping in the <strong>garden</strong> when he is happy…',
            'Yes. The fox is jumping in the garden when he is happy. But that is not the whole story of the garden story.' => '…The fox is jumping in the <strong>garden</strong> when he is happy. But…',
        ];
        $searchString = 'garden';
        foreach ($testArray as $testString => $testExpected) {
            $result = StringHelper::extractText($testString, $searchString);
            $result = StringHelper::replace($result, $searchString, '<strong>' . $searchString . '</strong>');
            self::assertSame($testExpected, $result, 'tested: ' . $testString);
        }

        // ----------------

        $testArray = [
            'Yes. The bird is flying in the wind. The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…flying in the wind. <strong>The fox is jumping in the garden</strong> when he…',
            'The bird is flying in the wind. The fox is jumping in the garden when he is happy. But that is not the whole story.' => '…in the wind. <strong>The fox is jumping in the garden</strong> when he is…',
            'The fox is jumping in the garden when he is happy. But that is not the whole story.' => '<strong>The fox is jumping in the garden</strong> when he is…',
            'Yes. The fox is jumping in the garden when he is happy. But that is not the whole story.' => 'Yes. <strong>The fox is jumping in the garden</strong> when he…',
            'Yes. The fox is jumping in the garden when he is happy. But that is not the whole story of the garden story.' => 'Yes. <strong>The fox is jumping in the garden</strong> when he is happy…',
        ];
        $searchString = 'The fox is jumping in the garden';
        foreach ($testArray as $testString => $testExpected) {
            $result = StringHelper::extractText($testString, $searchString);
            $result = StringHelper::replace($result, $searchString, '<strong>' . $searchString . '</strong>');
            self::assertSame($testExpected, $result, 'tested: ' . $testString);
        }
    }

    #[DataProvider('firstDataProvider')]
    public function test_first(string $expected, string $string, int $number): void
    {
        $actual = StringHelper::first($string, $number);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('hasLowerCaseDataProvider')]
    public function test_has_lower_case(bool $expected, string $string): void
    {
        $actual = StringHelper::hasLowerCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('hasUpperCaseDataProvider')]
    public function test_has_upper_case(bool $expected, string $string): void
    {
        $actual = StringHelper::hasUpperCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('htmlDecodeDataProvider')]
    public function test_html_decode(string $expected, string $string, int $flags = ENT_COMPAT): void
    {
        $actual = StringHelper::htmlDecode($string, $flags);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('htmlEncodeDataProvider')]
    public function test_html_encode(string $expected, string $string, int $flags = ENT_COMPAT): void
    {
        $actual = StringHelper::htmlEncode($string, $flags);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('humanizeDataProvider')]
    public function test_humanize(string $expected, string $string): void
    {
        $actual = StringHelper::humanize($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('indexOfDataProvider')]
    public function test_index_of(int|false $expected, string $haystack, string $needle, int $offset = 0, bool $caseSensitive = true): void
    {
        $actual = StringHelper::indexOf($haystack, $needle, $offset, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('indexOfLastDataProvider')]
    public function test_index_of_last(int|false $expected, string $haystack, string $needle, int $offset = 0, bool $caseSensitive = true): void
    {
        $actual = StringHelper::indexOfLast($haystack, $needle, $offset, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('insertDataProvider')]
    public function test_insert(string $expected, string $string, string $substring, int $index): void
    {
        $actual = StringHelper::insert($string, $substring, $index);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isDataProvider')]
    public function test_is(bool $expected, string $string, string $pattern): void
    {
        $actual = StringHelper::is($string, $pattern);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isAlphaDataProvider')]
    public function test_is_alpha(bool $expected, string $string): void
    {
        $actual = StringHelper::isAlpha($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isAlphanumericDataProvider')]
    public function test_is_alphanumeric(bool $expected, string $string): void
    {
        $actual = StringHelper::isAlphanumeric($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isBase64DataProvider')]
    public function test_is_base64(bool $expected, string $string): void
    {
        $actual = StringHelper::isBase64($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isBlankDataProvider')]
    public function test_is_blank(bool $expected, string $string): void
    {
        $actual = StringHelper::isBlank($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isHexadecimalDataProvider')]
    public function test_is_hexadecimal(bool $expected, string $string): void
    {
        $actual = StringHelper::isHexadecimal($string);
        self::assertSame($expected, $actual);
    }

    public function test_is_html(): void
    {
        $testArray = [
            '' => false,
            '<h1>test</h1>' => true,
            'test' => false,
            '<b>lall</b>' => true,
            'öäü<strong>lall</strong>' => true,
            ' <b>lall</b>' => true,
            '<b><b>lall</b>' => true,
            '</b>lall</b>' => true,
            '[b]lall[b]' => false,
            ' <test>κόσμε</test> ' => true,
        ];

        foreach ($testArray as $testString => $testResult) {
            $result = StringHelper::isHtml($testString);
            static::assertSame($result, $testResult);
        }
    }

    #[DataProvider('isJsonDataProvider')]
    public function test_is_json(bool $expected, string $string, bool $onlyArrayOrObjectResultsAreValid): void
    {
        $actual = StringHelper::isJson($string, $onlyArrayOrObjectResultsAreValid);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isLowerCaseDataProvider')]
    public function test_is_lower_case(bool $expected, string $string): void
    {
        $actual = StringHelper::isLowerCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('mb4DataProvider')]
    public function test_is_mb4(bool $expected, string $string): void
    {
        $actual = Str::containsMb4($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isSerializedDataProvider')]
    public function test_is_serialized(bool $expected, string $string): void
    {
        $actual = StringHelper::isSerialized($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('isUpperCaseDataProvider')]
    public function test_is_upper_case(bool $expected, string $string): void
    {
        $actual = StringHelper::isUpperCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('uuidDataProvider')]
    public function test_is_uuid(bool $expected, string $string): void
    {
        $actual = StringHelper::isUUID($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('whitespaceDataProvider')]
    public function test_is_whitespace(bool $expected, string $string): void
    {
        $actual = StringHelper::isWhitespace($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('linesDataProvider')]
    public function test_lines(int $expected, string $string): void
    {
        $actual = StringHelper::lines($string);
        self::assertCount($expected, $actual);
    }

    #[DataProvider('firstLineDataProvider')]
    public function test_first_line(string $expected, string $string): void
    {
        self::assertEquals($expected, StringHelper::firstLine($string));
    }

    public function test_line_wrap_after_word(): void
    {
        $testArray = [
            '' => "\n",
            ' ' => ' ' . "\n",
            'http:// moelleken.org' => 'http://' . "\n" . 'moelleken.org' . "\n",
            'http://test.de' => 'http://test.de' . "\n",
            'http://öäü.de' => 'http://öäü.de' . "\n",
            'http://menadwork.com' => 'http://menadwork.com' . "\n",
            'test.de' => 'test.de' . "\n",
            'test' => 'test' . "\n",
            '0123456 789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789' => '0123456' . "\n" . '789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789' . "\n",
        ];

        foreach ($testArray as $testString => $testResult) {
            $actual = StringHelper::lineWrapAfterWord($testString, 10);
            static::assertSame($testResult, $actual);
        }
    }

    #[DataProvider('lowerCaseFirstDataProvider')]
    public function test_lowercase_first(string $expected, string $string): void
    {
        $actual = StringHelper::lowercaseFirst($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('mb4EncodingDataProvider')]
    public function test_mb4_encoding(string $expected, string $string): void
    {
        $actual = StringHelper::encodeMb4($string);
        self::assertSame($expected, $actual);

        self::assertFalse(Str::containsMb4($actual));
    }

    #[DataProvider('padDataProvider')]
    public function test_pad(string $expected, string $string, int $length, string $padStr = ' ', string $padType = 'right'): void
    {
        $actual = StringHelper::pad($string, $length, $padStr, $padType);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('padBothDataProvider')]
    public function test_pad_both(string $expected, string $string, int $length, string $padStr = ' '): void
    {
        $actual = StringHelper::padBoth($string, $length, $padStr);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('padLeftDataProvider')]
    public function test_pad_left(string $expected, string $string, int $length, string $padStr = ' '): void
    {
        $actual = StringHelper::padLeft($string, $length, $padStr);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('padRightDataProvider')]
    public function test_pad_right(string $expected, string $string, int $length, string $padStr = ' '): void
    {
        $actual = StringHelper::padRight($string, $length, $padStr);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('prependDataProvider')]
    public function test_prepend(string $expected, string $string, string $prependString): void
    {
        $actual = StringHelper::prepend($string, $prependString);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('randomStringWithCharsDataProvider')]
    public function test_random_string_with_chars(string $string, int $length): void
    {
        $str = StringHelper::randomStringWithChars($string, $length);
        $strLen = mb_strlen($str);

        self::assertSame($length, $strLen);

        // Loop through the string and see if any of the characters aren't on the list of allowed chars.
        for ($i = 0; $i < $strLen; $i++) {
            if (mb_strpos($string, $str[$i]) === false) {
                $this->fail('Invalid chars');
            }
        }
    }

    #[DataProvider('randomStringDataProvider')]
    public function test_random_string(int $length = 36, bool $extendedChars = false): void
    {
        $random = StringHelper::randomString($length, $extendedChars);
        $len = strlen($random);
        self::assertSame($length, $len);

        if ($extendedChars) {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
        } else {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }

        foreach (str_split($random) as $char) {
            self::assertStringContainsString($char, $validChars);
        }
    }

    #[DataProvider('regexReplaceDataProvider')]
    public function test_regex_replace(string $expected, string $string, string $pattern, string $replacement, string $options = 'msr'): void
    {
        $actual = StringHelper::regexReplace($string, $pattern, $replacement, $options);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('removeHtmlDataProvider')]
    public function test_remove_html(string $expected, string $string, ?string $allowableTags = null): void
    {
        $actual = StringHelper::removeHtml($string, $allowableTags);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('removeHtmlBreakDataProvider')]
    public function test_remove_html_break(string $expected, string $string, string $replacement = ''): void
    {
        $actual = StringHelper::removeHtmlBreak($string, $replacement);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('removeLeftDataProvider')]
    public function test_remove_left(string $expected, string $string, string $substring): void
    {
        $actual = StringHelper::removeLeft($string, $substring);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('repeatDataProvider')]
    public function test_repeat(string $expected, string $string, int $multiplier): void
    {
        $actual = StringHelper::repeat($string, $multiplier);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('replaceAllDataProvider')]
    public function test_replace_all(string $expected, string $string, array $search, string|array $replacement, bool $caseSensitive = true): void
    {
        $actual = StringHelper::replaceAll($string, $search, $replacement, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('replaceBeginningDataProvider')]
    public function test_replace_beginning(string $expected, string $string, string $search, string $replacement): void
    {
        $actual = StringHelper::replaceBeginning($string, $search, $replacement);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('replaceFirstDataProvider')]
    public function test_replace_first(string $expected, string $string, string $search, string $replacement): void
    {
        $actual = StringHelper::replaceFirst($string, $search, $replacement);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('replaceLastDataProvider')]
    public function test_replace_last(string $expected, string $string, string $search, string $replacement): void
    {
        $actual = StringHelper::replaceLast($string, $search, $replacement);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('replaceEndingDataProvider')]
    public function test_replace_ending(string $expected, string $string, string $search, string $replacement): void
    {
        $actual = StringHelper::replaceEnding($string, $search, $replacement);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('reverseDataProvider')]
    public function test_reverse(string $expected, string $string): void
    {
        $actual = StringHelper::reverse($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('safeTruncateDataProvider')]
    public function test_safe_truncate(string $expected, string $string, int $length, string $substring = '', bool $ignoreDoNotSplitWordsForOneWord = true): void
    {
        $actual = StringHelper::safeTruncate($string, $length, $substring, $ignoreDoNotSplitWordsForOneWord);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('shortenAfterWordDataProvider')]
    public function test_shorten_after_word(string $expected, string $string, int $length, string $strAddOn): void
    {
        $actual = StringHelper::shortenAfterWord($string, $length, $strAddOn);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('shuffleDataProvider')]
    public function test_shuffle(string $string): void
    {
        $encoding = mb_internal_encoding();
        $result = StringHelper::shuffle($string);

        self::assertSame(
            mb_strlen($string, $encoding),
            mb_strlen($result, $encoding)
        );

        // Make sure that the chars are present after shuffle
        $length = mb_strlen($string, $encoding);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($string, $i, 1, $encoding);
            $countBefore = mb_substr_count($string, $char, $encoding);
            $countAfter = mb_substr_count($result, $char, $encoding);
            self::assertSame($countBefore, $countAfter);
        }
    }

    #[DataProvider('sliceDataProvider')]
    public function test_slice(string $expected, string $string, int $start, ?int $end = null): void
    {
        $actual = StringHelper::slice($string, $start, $end);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('slugifyDataProvider')]
    public function test_slugify(string $expected, string $string, string $replacement = '-', ?string $language = 'en'): void
    {
        $actual = StringHelper::slugify($string, $replacement, $language);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('splitDataProvider')]
    public function test_split(array $expected, string $string, string $splitter = ','): void
    {
        $actual = StringHelper::split($string, $splitter);
        self::assertSame($expected, $actual);
    }

    public function test_starts_with(): void
    {
        self::assertTrue(StringHelper::startsWith('thisisastring a', 't'));
        self::assertTrue(StringHelper::startsWith('', ''));
        self::assertTrue(StringHelper::startsWith('craft cms is awsome', 'craft c'));
        self::assertTrue(StringHelper::startsWith('😀😘', '😀'));
        self::assertTrue(StringHelper::startsWith('  ', ' '));

        self::assertFalse(StringHelper::startsWith('a ball is round', 'b'));
        self::assertFalse(StringHelper::startsWith('a ball is round', 'ball'));
        self::assertFalse(StringHelper::startsWith('29*@1*1209)*08231b**!@&712&(!&@', '!&@'));
    }

    #[DataProvider('startsWithAnyDataProvider')]
    public function test_starts_with_any(bool $expected, string $string, array $substrings, bool $caseSensitive = true): void
    {
        $actual = StringHelper::startsWithAny($string, $substrings, $caseSensitive);
        self::assertSame($expected, $actual);
    }

    public function test_strip_css_media_queries(): void
    {
        $testArray = [
            'test lall ' => 'test lall ',
            '' => '',
            ' ' => ' ',
            'test @media (min-width:660px){ .des-cla #mv-tiles{width:480px} } test ' => 'test  test ',
            'test @media only screen and (max-width: 950px) { .des-cla #mv-tiles{width:480px} }' => 'test ',
        ];

        foreach ($testArray as $testString => $testResult) {
            $actual = StringHelper::stripCssMediaQueries($testString);
            self::assertSame($testResult, $actual);
        }
    }

    public function test_strip_empty_html_tags(): void
    {
        $testArray = [
            '' => '',
            '<h1>test</h1>' => '<h1>test</h1>',
            'foo<h1></h1>bar' => 'foobar',
            '<h1></h1> ' => ' ',
            '</b></b>' => '</b></b>',
            'öäü<strong>lall</strong>' => 'öäü<strong>lall</strong>',
            ' b<b></b>' => ' b',
            '<b><b>lall</b>' => '<b><b>lall</b>',
            '</b>lall</b>' => '</b>lall</b>',
            '[b][/b]' => '[b][/b]',
        ];

        foreach ($testArray as $testString => $testResult) {
            $actual = StringHelper::stripEmptyHtmlTags($testString);
            self::assertSame($testResult, $actual);
        }
    }

    #[DataProvider('stripHtmlDataProvider')]
    public function test_strip_html(string $expected, string $string): void
    {
        $actual = StringHelper::stripHtml($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('stripWhitespaceDataProvider')]
    public function test_strip_whitespace(string $expected, string $string): void
    {
        $actual = StringHelper::stripWhitespace($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('substrDataProvider')]
    public function test_substr(string $expected, string $string, int $start, ?int $length = null): void
    {
        $actual = StringHelper::substr($string, $start, $length);
        self::assertSame($expected, $actual);
    }

    public function test_substring_count(): void
    {
        self::assertSame(2, StringHelper::countSubstrings('hello', 'l'));
        self::assertSame(1, StringHelper::countSubstrings('😀😘', '😘'));
        self::assertSame(3, StringHelper::countSubstrings('!@#$%^&*()^^', '^'));
        self::assertSame(4, StringHelper::countSubstrings('    ', ' '));
    }

    #[DataProvider('surroundDataProvider')]
    public function test_surround(string $expected, string $string, string $subString): void
    {
        $actual = StringHelper::surround($string, $subString);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('swapCaseDataProvider')]
    public function test_swap_case(string $expected, string $string): void
    {
        $actual = StringHelper::swapCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('tidyDataProvider')]
    public function test_tidy(string $expected, string $string): void
    {
        $actual = StringHelper::tidy($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('titleizeDataProvider')]
    public function test_titleize(string $expected, string $string, ?array $ignore = null): void
    {
        $actual = StringHelper::titleize($string, $ignore);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('titleizeForHumansDataProvider')]
    public function test_titleize_for_humans(string $expected, string $string, array $ignore = []): void
    {
        $actual = StringHelper::titleizeForHumans($string, $ignore);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toAsciiDataProvider')]
    public function test_to_ascii(string $expected, string $string, ?string $language = 'en'): void
    {
        $actual = StringHelper::toAscii($string, $language);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toBooleanDataProvider')]
    public function test_to_boolean(bool $expected, string $string): void
    {
        $actual = StringHelper::toBoolean($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toCamelCaseDataProvider')]
    public function test_to_camel_case(string $expected, string $string): void
    {
        $actual = StringHelper::toCamelCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toKebabCaseDataProvider')]
    public function test_to_kebab_case(string $expected, string $string): void
    {
        $actual = StringHelper::toKebabCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toLowerCaseDataProvider')]
    public function test_to_lower_case(string $expected, string $string): void
    {
        $actual = StringHelper::toLowerCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toPascalCaseDataProvider')]
    public function test_to_pascal_case(string $expected, string $string): void
    {
        $actual = StringHelper::toPascalCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('snakeCaseDataProvider')]
    public function test_to_snake_case(string $expected, string $string): void
    {
        $actual = StringHelper::toSnakeCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toSpacesDataProvider')]
    public function test_to_spaces(string $expected, string $string, int $tabLength = 4): void
    {
        $actual = StringHelper::toSpaces($string, $tabLength);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toStringDataProvider')]
    public function test_to_string(string $expected, mixed $object, string $glue = ','): void
    {
        $actual = StringHelper::toString($object, $glue);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toTabsDataProvider')]
    public function test_to_tabs(string $expected, string $string, int $tabLength = 4): void
    {
        $actual = StringHelper::toTabs($string, $tabLength);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toTitleCaseDataProvider')]
    public function test_to_title_case(string $expected, string $string): void
    {
        $actual = StringHelper::toTitleCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toTransliterateDataProvider')]
    public function test_to_transliterate(string $expected, string $string): void
    {
        $actual = StringHelper::toTransliterate($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toUppercaseDataProvider')]
    public function test_to_uppercase(string $expected, string $string): void
    {
        $actual = StringHelper::toUpperCase($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('toHandleDataProvider')]
    public function test_to_handle(string $expected, string $str)
    {
        self::assertSame($expected, StringHelper::toHandle($str));
    }

    #[DataProvider('trimDataProvider')]
    public function test_trim(string $expected, string $string): void
    {
        $actual = StringHelper::trim($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('trimLeftDataProvider')]
    public function test_trim_left(string $expected, string $string, ?string $chars = null): void
    {
        $actual = StringHelper::trimLeft($string, $chars);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('trimRightDataProvider')]
    public function test_trim_right(string $expected, string $string, ?string $chars = null): void
    {
        $actual = StringHelper::trimRight($string, $chars);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('upperCamelizeDataProvider')]
    public function test_upper_camelize(string $expected, string $string): void
    {
        $actual = StringHelper::upperCamelize($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('uppercaseFirstDataProvider')]
    public function test_uppercase_first(string $expected, string $string): void
    {
        $actual = StringHelper::upperCaseFirst($string);
        self::assertSame($expected, $actual);
    }

    public function test_utf8_definition(): void
    {
        self::assertSame('UTF-8', StringHelper::UTF8);
    }

    public function test_uuid(): void
    {
        $uuid = StringHelper::UUID();
        self::assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid);
        self::assertSame(36, strlen($uuid));
    }

    #[DataProvider('collapseWhitespaceDataProvider')]
    public function test_whitespace_collapse(string $expected, string $string): void
    {
        $actual = StringHelper::collapseWhitespace($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('idnToUtf8EmailDataProvider')]
    public function test_idn_to_utf8_email(string $expected, string $string): void
    {
        $actual = StringHelper::idnToUtf8Email($string);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('emojiToShortcodesDataProvider')]
    public function test_emoji_to_shortcodes(string $expected, string $str)
    {
        self::assertSame($expected, Str::emojiToShortcodes($str));
    }

    #[DataProvider('shortcodesToEmojiDataProvider')]
    public function test_shortcodes_to_emoji(string $expected, string $str)
    {
        self::assertSame($expected, Str::shortcodesToEmoji($str));
    }

    #[DataProvider('escapeShortcodesDataProvider')]
    public function test_escape_shortcodes(string $expected, string $str)
    {
        self::assertSame($expected, Str::escapeShortcodes($str));
    }

    #[DataProvider('unescapeShortcodesDataProvider')]
    public function test_unescape_shortcodes(string $expected, string $str)
    {
        self::assertSame($expected, Str::unescapeShortcodes($str));
    }

    public static function substrDataDataProvider(): array
    {
        return [
            ['st s', 'test s 2 s zw 2', 2, 4],
            [' 😁😂😘', '😂 😁😂😘 😁😂😘 😁', 1, 4],
            ['test CRAF', 'Test test CRAFT cms !@#$%^&  *(abc)', 5, 9],
            ['Craft Cms', 'Craft Cms', 0, 1000],
            ['AFT CMS', 'CRAFT CMS', 2, 1000],
            ['CRAFTCMS', 'CRAFTCMS', 0],
            ['AFTCMS', 'CRAFTCMS', 2],
            ['', '', 2, 5],
            ['', '😘', 1, 5],
            ['#$%  ', '!@#$%  ^&*()', 2, 5],
        ];
    }

    public static function swapCaseDataDataProvider(): array
    {
        return [
            ['TEST S 2 S ZW 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['tEST TEST craft CMS !@#$%^&  *(ABC)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['cRAFT cMS', 'Craft Cms'],
            ['craft cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 aLPHAn nUMERIC', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function toTitleCaseDataProvider(): array
    {
        return [
            ['Test S 2 S Zw 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['Test Test Craft Cms !@#$%^&  *(Abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['Craft Cms', 'Craft Cms'],
            ['Craft Cms', 'CRAFT CMS'],
            ['Craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 Alphan Numeric', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function toLowerCaseDataProvider(): array
    {
        return [
            ['test s 2 s zw 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['test test craft cms !@#$%^&  *(abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craft cms', 'Craft Cms'],
            ['craft cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 alphan numeric', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function indexOfDataProvider(): array
    {
        return [
            [6, 'foo & bar', 'Bar', 0, false],
            [6, 'foo & bar', 'bar', 0, false],
            [false, 'foo & bar', 'Baz', 0, false],
            [false, 'foo & bar', 'bAz', 0, false],
            [0, 'foo & bar & foo', 'foO', 0, false],
            [12, 'foo & bar & foo', 'fOO', 5, false],
            [6, 'fòô & bàř', 'bàř', 0, false],
            [false, 'fòô & bàř', 'baz', 0, false],
            [0, 'fòô & bàř & fòô', 'fòô', 0, false],
            [12, 'fòô & bàř & fòô', 'fòÔ', 5, false],
            [6, 'foo & bar', 'bar', 0, true],
            [6, 'foo & bar', 'bar', 0, true],
            [false, 'foo & bar', 'baz', 0, true],
            [false, 'foo & bar', 'baz', 0, true],
            [0, 'foo & bar & foo', 'foo', 0, true],
            [12, 'foo & bar & foo', 'foo', 5, true],
            [6, 'fòô & bàř', 'bàř', 0, true],
            [false, 'fòô & bàř', 'baz', 0, true],
            [0, 'fòô & bàř & fòô', 'fòô', 0, true],
            [12, 'fòô & bàř & fòô', 'fòô', 5, true],
        ];
    }

    public static function camelCaseDataProvider(): array
    {
        return [
            ['craftCms', 'Craft Cms'],
            ['cRAFTCMS', 'CRAFT CMS'],
            ['cRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['!@#$%^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function endsWithDataProvider(): array
    {
        return [
            [true, 'thisisastring a', 'a'],
            [true, '', ''],
            [true, 'craft cms is awsome', 's awsome'],
            [true, '', ''],
            [true, '😀😘', '😘'],
            [true, '😀😘', '😘'],
            [true, '    ', ' '],
            [true, '29*@1*1209)*08231b**!@&712&(!&@', '!&@'],
            [false, 'a ball is round', 'square'],
            [false, 'a ball is round', 'ball'],
        ];
    }

    public static function uppercaseFirstDataProvider(): array
    {
        return [
            ['Craftcms', 'craftcms'],
            ['2craftcms', '2craftcms'],
            [' craftcms', ' craftcms'],
            [' ', ' '],
        ];
    }

    public static function uuidDataProvider(): array
    {
        return [
            [true, StringHelper::UUID()],
            [true, 'c3d6a75d-5b98-4048-8106-8cc2de4af159'],
            [true, 'c74e8f78-c052-4978-b0e8-77a307f7b946'],
            [true, '469e6ed2-f270-458a-a80e-173821fee715'],
            [true, '00000000-0000-0000-0000-000000000000'],
            [false, StringHelper::UUID() . StringHelper::UUID()],
            [false, 'abc'],
            [false, '123'],
            [false, ''],
            [false, ' '],
            [false, '!@#$%^&*()'],
            [false, '469e6ed2-🎧𢵌😀😘-458a-a80e-173821fee715'],
            [false, '&*%!$^!#-5b98-4048-8106-8cc2de4af159'],
        ];
    }

    public static function stripHtmlDataProvider(): array
    {
        return [
            ['hello', '<p>hello</p>'],
            ['hello', '<>hello</>'],
            ['hello', '<script src="https://">hello</script>'],
            ['', '<link src="#">'],
            ['hello', '<random-tag src="#">hello</random-tag>'],
            ['hellohellohello', '<div>hello<p>hello</p>hello</div>'],
        ];
    }

    public static function firstDataProvider(): array
    {
        return [
            ['', '', 1],
            ['qwertyuiopas', 'qwertyuiopasdfghjklzxcvbnm', 12],
            ['QWE', 'QWERTYUIOPASDFGHJKLZXCVBNM', 3],
            ['12', '123456789', 2],
            ['!@#$%^', '!@#$%^', 100],
            ['🎧𢵌', '🎧𢵌😀😘⛄', 2],
        ];
    }

    public static function toAsciiDataProvider(): array
    {
        return [
            ['', ''],
            ['abc', 'abc'],
            ['123', '123'],
            ['!@#$%^', '!@#$%^'],
            ['', '🎧𢵌😀😘⛄'],
            ['abc123', '🎧𢵌😀abc😘123⛄'],
            ['ae', 'ä', 'de'], // NFD → NFC conversion (https://github.com/craftcms/cms/issues/6923)
        ];
    }

    public static function charsAsArrayDataProvider(): array
    {
        return [
            [[], ''],
            [['a', 'b', 'c'], 'abc'],
            [['1', '2', '3'], '123'],
            [['!', '@', '#', '$', '%', '^'], '!@#$%^'],
            [['🎧', '𢵌', '😀', '😘', '⛄'], '🎧𢵌😀😘⛄'],
        ];
    }

    public static function mb4DataProvider(): array
    {
        return [
            [true, '😀😘'],
            [true, 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890😘'],
            [true, '!@#$%^&*()_🎧'],
            [true, '!@#$%^&*(𢵌)_'],
            [false, 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890'],
            [false, '!@#$%^&*()_'],
            [false, '⛄'],
            [false, ''],
            [true, '𨳊'],
        ];
    }

    public static function snakeCaseDataProvider(): array
    {
        return [
            ['c_r_a_f_t_c_m_s', 'CRAFT CMS'],
            ['c_r_a_f_t_c_m_s', 'CRAFTCMS'],
            ['', ''],
            ['i😘_u', 'I 😘 U'],
            ['22_alpha_n_numeric', '22 AlphaN Numeric'],
            ['foo_bar', 'fooBar'],
        ];
    }

    public static function delimitDataProvider(): array
    {
        return [
            ['', '    ', '|'],
            ['hello|iam|astring', 'HelloIamAstring', '|'],
            ['😀😁😂🤣😃😄😅😆', '😀😁😂🤣😃😄😅😆', '|'],
            ['hello iam astring', 'HelloIamAstring', ' '],
            ['hello!@#iam!@#astring', 'HelloIamAstring', '!@#'],
            ['hello😀😁😂iam😀😁😂astring', 'HelloIamAstring', '😀😁😂'],
            ['hello😀😁😂iam😀😁😂a2string', 'HelloIamA2string', '😀😁😂'],
        ];
    }

    public static function splitDataProvider(): array
    {
        return [
            [['22', '23'], '22, 23'],
            [['ab', 'cd'], 'ab,cd'],
            [['22', '23'], '22,23, '],
            [['22', '23'], '22| 23', '|'],
            [['22,', '23'], '22,/ 23', '/'],
            [['22', '23'], '22😀23', '😀'],
            [[], ''],
        ];
    }

    public static function whitespaceDataProvider(): array
    {
        return [
            [true, ''],
            [true, ' '],
            [true, '                                           '],
            [false, 'qwe rty     uio   pasd'],
            [false, 'Q                     W E'],
            [false, '    12345   67     89     '],
            [false, '! @     #$     %       ^'],
            [false, '🎧𢵌       😀😘⛄       '],
            [false, 'craftcms'],
            [false, '/@#$%^&*'],
            [false, 'hello,people'],
        ];
    }

    public static function collapseWhitespaceDataProvider(): array
    {
        return [
            ['', '  '],
            ['', '                                           '],
            ['qwe rty uio pasd', 'qwe rty     uio   pasd'],
            ['Q W E', 'Q                     W E'],
            ['12345 67 89', '    12345   67     89     '],
            ['! @ #$ % ^', '! @     #$     %       ^'],
            ['🎧𢵌 😀😘⛄', '🎧𢵌       😀😘⛄       '],
        ];
    }

    public static function randomStringWithCharsDataProvider(): array
    {
        return [
            ['asdfghjklxcvbnmqwertyuiop', 10],
            ['1234567890', 22],
            ['!@#$%^&*()_{}|:"<>?', 0],
            ['!@#$%^&*()_{}|:"<>?', 8],
            ['                           ', 8],
            'tabs' => ['              ', 4],
            ['asdfghjklxcvbnmqwertyuiop', 10],
        ];
    }

    public static function mb4EncodingDataProvider(): array
    {
        return [
            ['&#x1f525;', '🔥'],
            ['&#x1f525;', '&#x1f525;'],
            ['&#x1f1e6;&#x1f1fa;', '🇦🇺'],
            ['&#x102cd;', '𐋍'],
            ['asdfghjklqwertyuiop1234567890!@#$%^&*()_+', 'asdfghjklqwertyuiop1234567890!@#$%^&*()_+'],
            ['&#x102cd;&#x1f1e6;&#x1f1fa;&#x1f525;', '𐋍🇦🇺🔥'],
            'ensure-non-mb4-is-ignored' => ['&#x102cd;1234567890&#x1f1e6;&#x1f1fa; &#x1f525;', '𐋍1234567890🇦🇺 🔥'],
        ];
    }

    public static function convertToUtf8DataProvider(): array
    {
        return [
            ['κόσμε', 'κόσμε'],
            ['\x74\x65\x73\x74', '\x74\x65\x73\x74'],
            ['craftcms', 'craftcms'],
            ['😂😁', '😂😁'],
            ['Foo © bar 𝌆 baz ☃ qux', 'Foo © bar 𝌆 baz ☃ qux'],
            ['İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±', 'İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±'],
        ];
    }

    public static function encDecDataProvider(): array
    {
        return [
            ['1234567890asdfghjkl'],
            ['😂😁'],
            ['!@#$%^&*()_+{}|:"<>?'],
        ];
    }

    public static function afterFirstDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['ar', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['></b>', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            ['<b></b>', ' b<b></b>', 'b', true],
            ['><b>lall</b>', '<b><b>lall</b>', 'b', true],
            ['>lall</b>', '</b>lall</b>', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['][/b]', '[b][/b]', 'b', true],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', true],
            ['κόσμbε', 'bκόσμbε', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['ar', 'foo<h1></h1>Bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['></b>', '</B></b>', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            ['></b>B', ' <b></b>B', 'B', false],
            ['><b>lall</b>', '<b><b>lall</b>', 'b', false],
            ['>lall</b>', '</b>lall</b>', 'b', false],
            ['][/B]', '[B][/B]', 'b', false],
            ['][/b]', '[B][/b]', 'B', false],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', false],
            ['κόσμbε', 'bκόσμbε', 'B', false],
        ];
    }

    public static function afterLastDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['ar', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['>', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            ['>', ' b<b></b>', 'b', true],
            ['>', '<b><b>lall</b>', 'b', true],
            ['>', '</b>lall</b>', 'b', true],
            [']', '[b][/b]', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['ar', 'foo<h1></h1>bar', 'b', false],
            ['ar', 'foo<h1></h1>Bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            ['>', ' b<b></b>', 'b', false],
            ['>', '<b><b>lall</b>', 'b', false],
            ['>', '<b><B>lall</B>', 'b', false],
            [']', '[b][/b]', 'b', false],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', false],
        ];
    }

    public static function toStringDataProvider(): array
    {
        return [
            ['test', 'test'],
            ['', new stdClass()],
            ['ima string', new ToString('ima string')],
            ['t,e,s,t', ['t', 'e', 's', 't']],
            ['t|e|s|t', ['t', 'e', 's', 't'], '|'],
            ['valid', LicenseKeyStatus::Valid],
        ];
    }

    public static function randomStringDataProvider(): array
    {
        return [
            [],
            [50, false],
            [55, true],
        ];
    }

    public static function toPascalCaseDataProvider(): array
    {
        return [
            ['TestS2SZw2', 'test s 2 s zw 2'],
            ['', '😂 😁'],
            ['TestTestCraftCmsAbc', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['CraftCms', 'Craft Cms'],
            ['CraftCms', 'CRAFT CMS'],
            ['Craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['', '!@#$%  ^&*()'],
        ];
    }

    public static function toCamelCaseDataProvider(): array
    {
        return [
            ['testS2SZw2', 'test s 2 s zw 2'],
            ['iAmSo😂😁!', 'I am so 😂 😁!'],
            ['testTestCRAFTCms!@#$%^&*(abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craftCms', 'Craft Cms'],
            ['cRAFTCMS', 'CRAFT CMS'],
            ['cRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['!@#$%^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function toKebabCaseDataProvider(): array
    {
        return [
            ['test-s-2-s-zw-2', 'test s 2 s zw 2'],
            ['test-s-0-s-zw-2', 'test s 0 s zw 2'],
            ['', '😂 😁'],
            ['test-test-craft-cms-abc', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craft-cms', 'Craft Cms'],
            ['craft-cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22-alpha-n-numeric', '22 AlphaN Numeric'],
            ['', '!@#$%  ^&*()'],
        ];
    }

    public static function linesDataProvider(): array
    {
        return [
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
        ];
    }

    public static function firstLineDataProvider(): array
    {
        return [
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
        ];
    }

    public static function toUppercaseDataProvider(): array
    {
        return [
            ['TEST S 2 S ZW 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['TEST TEST CRAFT CMS !@#$%^&  *(ABC)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['CRAFT CMS', 'Craft Cms'],
            ['CRAFT CMS', 'CRAFT CMS'],
            ['CRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 ALPHAN NUMERIC', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    public static function toHandleDataProvider(): array
    {
        return [
            ['foo', 'FOO'],
            ['fooBar', 'FOO BAR'],
            ['fooBar', 'Fo’o Bar'],
            ['fooBarBaz', 'Foo Ba’r   Baz'],
            ['fooBar', '0 Foo Bar'],
            ['fooBar', 'Foo!Bar'],
            ['fooBar', 'Foo,Bar'],
            ['fooBar', 'Foo/Bar'],
            ['fooBar', 'Foo\\Bar'],
        ];
    }

    public static function trimDataProvider(): array
    {
        return [
            ['😂 😁', '😂 😁 '],
            ['', ''],
            ['😘', '😘'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
            ['\x09Example string\x0A', '\x09Example string\x0A'],
            ['\t\tThese are a few words :) ...', '\t\tThese are a few words :) ...  '],
        ];
    }

    public static function appendDataProvider(): array
    {
        return [
            ['foobar', 'foo', 'bar'],
            ['fòôbàř', 'fòô', 'bàř'],
        ];
    }

    public static function atDataProvider(): array
    {
        return [
            ['f', 'foo bar', 0],
            ['o', 'foo bar', 1],
            ['r', 'foo bar', 6],
            ['', 'foo bar', 7],
            ['f', 'fòô bàř', 0],
            ['ò', 'fòô bàř', 1],
            ['ř', 'fòô bàř', 6],
            ['', 'fòô bàř', 7],
        ];
    }

    public static function betweenDataProvider(): array
    {
        return [
            ['', 'foo', '{', '}'],
            ['', '{foo', '{', '}'],
            ['foo', '{foo}', '{', '}'],
            ['{foo', '{{foo}', '{', '}'],
            ['', '{}foo}', '{', '}'],
            ['foo', '}{foo}', '{', '}'],
            ['foo', 'A description of {foo} goes here', '{', '}'],
            ['bar', '{foo} and {bar}', '{', '}', 1],
            ['', 'fòô', '{', '}', 0],
            ['', '{fòô', '{', '}', 0],
            ['fòô', '{fòô}', '{', '}', 0],
            ['{fòô', '{{fòô}', '{', '}', 0],
            ['', '{}fòô}', '{', '}', 0],
            ['fòô', '}{fòô}', '{', '}', 0],
            ['fòô', 'A description of {fòô} goes here', '{', '}', 0],
            ['bàř', '{fòô} and {bàř}', '{', '}', 1],
        ];
    }

    public static function camelizeDataProvider(): array
    {
        return [
            ['camelCase', 'CamelCase'],
            ['camelCase', 'Camel-Case'],
            ['camelCase', 'camel case'],
            ['camelCase', 'camel -case'],
            ['camelCase', 'camel - case'],
            ['camelCase', 'camel_case'],
            ['camelCTest', 'camel c test'],
            ['stringWith1Number', 'string_with1number'],
            ['stringWith22Numbers', 'string-with-2-2 numbers'],
            ['dataRate', 'data_rate'],
            ['backgroundColor', 'background-color'],
            ['yesWeCan', 'yes_we_can'],
            ['mozSomething', '-moz-something'],
            ['carSpeed', '_car_speed_'],
            ['serveHTTP', 'ServeHTTP'],
            ['1Camel2Case', '1camel2case'],
            ['camelΣase', 'camel σase'],
            ['στανιλCase', 'Στανιλ case'],
            ['σamelCase', 'σamel  Case'],
        ];
    }

    /**
     * @return array
     */
    public static function charsDataProvider(): array
    {
        return [
            [[], ''],
            [['T', 'e', 's', 't'], 'Test'],
            [['F', 'ò', 'ô', ' ', 'B', 'à', 'ř'], 'Fòô Bàř'],
        ];
    }

    public static function containsAllDataProvider(): array
    {
        // One needle
        $singleNeedle = array_map(
            static function($array) {
                $array[2] = [$array[2]];

                return $array;
            },
            static::containsDataProvider()
        );
        $provider = [
            // One needle
            [false, 'Str contains foo bar', []],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας']],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true],
            [false, 'Str contains foo bar', ['Foo', 'bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar']],
            [false, 'Str contains foo bar', ['foo bar ', 'bar']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba'], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false],
        ];

        return array_merge($singleNeedle, $provider);
    }

    public static function containsAnyDataProvider(): array
    {
        // One needle
        $singleNeedle = array_map(
            static function($array) {
                $array[2] = [$array[2]];

                return $array;
            },

            static::containsDataProvider()
        );

        $provider = [
            // No needles
            [false, 'Str contains foo bar', []],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας']],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true],
            [false, 'Str contains foo bar', ['Foo', 'Bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar ']],
            [false, 'Str contains foo bar', ['foo bar ', '  foo']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba '], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false],
        ];

        return array_merge($singleNeedle, $provider);
    }

    public static function containsDataProvider(): array
    {
        return [
            [true, 'Str contains foo bar', 'foo bar'],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%'],
            [true, 'Ο συγγραφέας είπε', 'συγγραφέας'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å´¥©', true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å˚ ∆', true],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'øœ¬', true],
            [false, 'Str contains foo bar', 'Foo bar'],
            [false, 'Str contains foo bar', 'foobar'],
            [false, 'Str contains foo bar', 'foo bar '],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', true],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßå˚', true],
            [true, 'Str contains foo bar', 'Foo bar', false],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%', false],
            [true, 'Ο συγγραφέας είπε', 'ΣΥΓΓΡΑΦΈΑΣ', false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å´¥©', false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å˚ ∆', false],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'ØŒ¬', false],
            [false, 'Str contains foo bar', 'foobar', false],
            [false, 'Str contains foo bar', 'foo bar ', false],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', false],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßÅ˚', false],
        ];
    }

    public static function countSubstrDataProvider(): array
    {
        return [
            [0, '', 'foo'],
            [0, 'foo', 'bar'],
            [1, 'foo bar', 'foo'],
            [2, 'foo bar', 'o'],
            [0, '', 'fòô'],
            [0, 'fòô', 'bàř'],
            [1, 'fòô bàř', 'fòô'],
            [2, 'fôòô bàř', 'ô'],
            [0, 'fÔÒÔ bàř', 'ô'],
            [0, 'foo', 'BAR', false],
            [1, 'foo bar', 'FOo', false],
            [2, 'foo bar', 'O', false],
            [1, 'fòô bàř', 'fÒÔ', false],
            [2, 'fôòô bàř', 'Ô', false],
            [2, 'συγγραφέας', 'Σ', false],
        ];
    }

    public static function dasherizeDataProvider(): array
    {
        return [
            ['test-case', 'testCase'],
            ['test-case', 'Test-Case'],
            ['test-case', 'test case'],
            ['-test-case', '-test -case'],
            ['test-case', 'test - case'],
            ['test-case', 'test_case'],
            ['test-c-test', 'test c test'],
            ['test-d-case', 'TestDCase'],
            ['test-c-c-test', 'TestCCTest'],
            ['string-with1number', 'string_with1number'],
            ['string-with-2-2-numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['data-rate', 'dataRate'],
            ['car-speed', 'CarSpeed'],
            ['yes-we-can', 'yesWeCan'],
            ['background-color', 'backgroundColor'],
            ['dash-σase', 'dash Σase'],
            ['στανιλ-case', 'Στανιλ case'],
            ['σash-case', 'Σash  Case'],
        ];
    }

    public static function endsWithAnyDataProvider(): array
    {
        return [
            [true, 'foo bars', ['foo', 'o bars']],
            [true, 'FOO bars', ['foo', 'o bars'], false],
            [true, 'FOO bars', ['foo', 'o BARs'], false],
            [true, 'FÒÔ bàřs', ['foo', 'ô bàřs'], false],
            [true, 'fòô bàřs', ['foo', 'ô BÀŘs'], false],
            [false, 'foo bar', ['foo']],
            [false, 'foo bar', ['foo', 'foo bars']],
            [false, 'FOO bar', ['foo', 'foo bars']],
            [false, 'FOO bars', ['foo', 'foo BARS']],
            [false, 'FÒÔ bàřs', ['fòô', 'fòô bàřs'], true],
            [false, 'fòô bàřs', ['fòô', 'fòô BÀŘS'], true],
        ];
    }

    public static function ensureLeftDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 'f'],
            ['foobar', 'foobar', 'foo'],
            ['foo/foobar', 'foobar', 'foo/'],
            ['http://foobar', 'foobar', 'http://'],
            ['http://foobar', 'http://foobar', 'http://'],
            ['fòôbàř', 'fòôbàř', 'f'],
            ['fòôbàř', 'fòôbàř', 'fòô'],
            ['fòô/fòôbàř', 'fòôbàř', 'fòô/'],
            ['http://fòôbàř', 'fòôbàř', 'http://'],
            ['http://fòôbàř', 'http://fòôbàř', 'http://'],
        ];
    }

    public static function ensureRightDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 'r'],
            ['foobar', 'foobar', 'bar'],
            ['foobar/bar', 'foobar', '/bar'],
            ['foobar.com/', 'foobar', '.com/'],
            ['foobar.com/', 'foobar.com/', '.com/'],
            ['fòôbàř', 'fòôbàř', 'ř'],
            ['fòôbàř', 'fòôbàř', 'bàř'],
            ['fòôbàř/bàř', 'fòôbàř', '/bàř'],
            ['fòôbàř.com/', 'fòôbàř', '.com/'],
            ['fòôbàř.com/', 'fòôbàř.com/', '.com/'],
        ];
    }

    public static function escapeDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof &lt;3', 'raboof <3'],
            ['řàbôòf&lt;foo&lt;lall&gt;&gt;&gt;', 'řàbôòf<foo<lall>>>'],
            ['řàb &lt;ô&gt;òf', 'řàb <ô>òf'],
            ['&lt;∂∆ onerro=&quot;alert(xss)&quot;&gt; ˚åß', '<∂∆ onerro="alert(xss)"> ˚åß'],
            ['&#039;œ … &#039;’)', '\'œ … \'’)'],
        ];
    }

    public static function hasLowerCaseDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'foobar'],
            [false, 'FOO BAR'],
            [true, 'fOO BAR'],
            [true, 'foO BAR'],
            [true, 'FOO BAr'],
            [true, 'Foobar'],
            [false, 'FÒÔBÀŘ'],
            [true, 'fòôbàř'],
            [true, 'fòôbàř2'],
            [true, 'Fòô bàř'],
            [true, 'fòôbÀŘ'],
        ];
    }

    public static function hasUpperCaseDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'FOOBAR'],
            [false, 'foo bar'],
            [true, 'Foo bar'],
            [true, 'FOo bar'],
            [true, 'foo baR'],
            [true, 'fOOBAR'],
            [false, 'fòôbàř'],
            [true, 'FÒÔBÀŘ'],
            [true, 'FÒÔBÀŘ2'],
            [true, 'fÒÔ BÀŘ'],
            [true, 'FÒÔBàř'],
        ];
    }

    public static function htmlDecodeDataProvider(): array
    {
        return [
            ['&', '&amp;'],
            ['"', '&quot;'],
            ["'", '&#039;', ENT_QUOTES],
            ['<', '&lt;'],
            ['>', '&gt;'],
        ];
    }

    public static function htmlEncodeDataProvider(): array
    {
        return [
            ['&amp;', '&'],
            ['&quot;', '"'],
            ['&#039;', "'", ENT_QUOTES],
            ['&lt;', '<'],
            ['&gt;', '>'],
        ];
    }

    public static function humanizeDataProvider(): array
    {
        return [
            ['Author', 'author_id'],
            ['Test user', ' _test_user_'],
            ['Συγγραφέας', ' συγγραφέας_id '],
        ];
    }

    public static function indexOfLastDataProvider(): array
    {
        return [
            [6, 'foo & bar', 'bar', 0, true],
            [6, 'foo & bar', 'bar', 0, true],
            [false, 'foo & bar', 'baz', 0, true],
            [false, 'foo & bar', 'baz', 0, true],
            [12, 'foo & bar & foo', 'foo', 0, true],
            [0, 'foo & bar & foo', 'foo', -5, true],
            [6, 'fòô & bàř', 'bàř', 0, true],
            [false, 'fòô & bàř', 'baz', 0, true],
            [12, 'fòô & bàř & fòô', 'fòô', 0, true],
            [0, 'fòô & bàř & fòô', 'fòô', -5, true],
            [6, 'foo & bar', 'Bar', 0, false],
            [6, 'foo & bar', 'bAr', 0, false],
            [false, 'foo & bar', 'baZ', 0, false],
            [false, 'foo & bar', 'baZ', 0, false],
            [12, 'foo & bar & foo', 'fOo', 0, false],
            [0, 'foo & bar & foo', 'fOO', -5, false],
            [6, 'fòô & bàř', 'bàř', 0, false],
            [false, 'fòô & bàř', 'baz', 0, false],
            [12, 'fòô & bàř & fòô', 'fòô', 0, false],
            [0, 'fòô & bàř & fòô', 'fòÔ', -5, false],
        ];
    }

    public static function insertDataProvider(): array
    {
        return [
            ['foo bar', 'oo bar', 'f', 0],
            ['foo bar', 'f bar', 'oo', 1],
            ['f bar', 'f bar', 'oo', 20],
            ['foo bar', 'foo ba', 'r', 6],
            ['fòôbàř', 'fòôbř', 'à', 4],
            ['fòô bàř', 'òô bàř', 'f', 0],
            ['fòô bàř', 'f bàř', 'òô', 1],
            ['fòô bàř', 'fòô bà', 'ř', 6],
        ];
    }

    public static function isAlphaDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'foobar2'],
            [true, 'fòôbàř'],
            [false, 'fòô bàř'],
            [false, 'fòôbàř2'],
            [true, 'ҠѨњфгШ'],
            [false, 'ҠѨњ¨ˆфгШ'],
            [true, '丹尼爾'],
        ];
    }

    public static function isAlphanumericDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar1'],
            [false, 'foo bar'],
            [false, 'foobar2"'],
            [false, "\nfoobar\n"],
            [true, 'fòôbàř1'],
            [false, 'fòô bàř'],
            [false, 'fòôbàř2"'],
            [true, 'ҠѨњфгШ'],
            [false, 'ҠѨњ¨ˆфгШ'],
            [true, '丹尼爾111'],
            [true, 'دانيال1'],
            [false, 'دانيال1 '],
        ];
    }

    public static function isBase64DataProvider(): array
    {
        return [
            [false, ' '],
            [true, base64_encode('FooBar')],
            [true, base64_encode(' ')],
            [true, base64_encode('FÒÔBÀŘ')],
            [true, base64_encode('συγγραφέας')],
            [false, 'Foobar'],
        ];
    }

    public static function isBlankDataProvider(): array
    {
        return [
            [true, ''],
            [true, ' '],
            [true, "\n\t "],
            [true, "\n\t  \v\f"],
            [false, "\n\t a \v\f"],
            [false, "\n\t ' \v\f"],
            [false, "\n\t 2 \v\f"],
            [true, ''],
            [true, ' '], // no-break space (U+00A0)
            [true, '           '], // spaces U+2000 to U+200A
            [true, ' '], // narrow no-break space (U+202F)
            [true, ' '], // medium mathematical space (U+205F)
            [true, '　'], // ideographic space (U+3000)
            [false, '　z'],
            [false, '　1'],
        ];
    }

    public static function isHexadecimalDataProvider(): array
    {
        return [
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
        ];
    }

    public static function isJsonDataProvider(): array
    {
        return [
            [false, '', true],
            [false, '  ', true],
            [false, 'null', true],
            [false, 'true', true],
            [false, 'false', true],
            [true, '[]', true],
            [true, '{}', true],
            [false, '123', true],
            [true, '{"foo": "bar"}', true],
            [false, '{"foo":"bar",}', true],
            [false, '{"foo"}', true],
            [true, '["foo"]', true],
            [false, '{"foo": "bar"]', true],
            [false, '123', true],
            [true, '{"fòô": "bàř"}', true],
            [false, '{"fòô":"bàř",}', true],
            [false, '{"fòô"}', true],
            [false, '["fòô": "bàř"]', true],
            [true, '["fòô"]', true],
            [false, '{"fòô": "bàř"]', true],
        ];
    }

    public static function isLowerCaseDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'Foobar'],
            [true, 'fòôbàř'],
            [false, 'fòôbàř2'],
            [false, 'fòô bàř'],
            [false, 'fòôbÀŘ'],
        ];
    }

    public static function isDataProvider(): array
    {
        return [
            [true, 'Gears\\String\\Str', 'Gears\\String\\Str'],
            [true, 'Gears\\String\\Str', 'Gears\\*\\Str'],
            [true, 'Gears\\String\\Str', 'Gears\\*\\*'],
            [true, 'Gears\\String\\Str', '*\\*\\*'],
            [true, 'Gears\\String\\Str', '*\\String\\*'],
            [true, 'Gears\\String\\Str', '*\\*\\Str'],
            [true, 'Gears\\String\\Str', '*\\Str'],
            [true, 'Gears\\String\\Str', '*'],
            [true, 'Gears\\String\\Str', '**'],
            [true, 'Gears\\String\\Str', '****'],
            [true, 'Gears\\String\\Str', '*Str'],
            [false, 'Gears\\String\\Str', '*\\'],
            [false, 'Gears\\String\\Str', 'Gears-*-*'],
        ];
    }

    public static function isSerializedDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'a:1:{s:3:"foo";s:3:"bar";}'],
            [false, 'a:1:{s:3:"foo";s:3:"bar"}'],
            [true, serialize(['foo' => 'bar'])],
            [true, 'a:1:{s:5:"fòô";s:5:"bàř";}'],
            [false, 'a:1:{s:5:"fòô";s:5:"bàř"}'],
            [true, serialize(['fòô' => 'bár'])],
        ];
    }

    public static function isUpperCaseDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'FOOBAR'],
            [false, 'FOO BAR'],
            [false, 'fOOBAR'],
            [true, 'FÒÔBÀŘ'],
            [false, 'FÒÔBÀŘ2'],
            [false, 'FÒÔ BÀŘ'],
            [false, 'FÒÔBàř'],
        ];
    }

    public static function lastDataProvider(): array
    {
        return [
            ['', 'foo bar', -5],
            ['', 'foo bar', 0],
            ['r', 'foo bar', 1],
            ['bar', 'foo bar', 3],
            ['foo bar', 'foo bar', 7],
            ['foo bar', 'foo bar', 8],
            ['', 'fòô bàř', -5],
            ['', 'fòô bàř', 0],
            ['ř', 'fòô bàř', 1],
            ['bàř', 'fòô bàř', 3],
            ['fòô bàř', 'fòô bàř', 7],
            ['fòô bàř', 'fòô bàř', 8],
        ];
    }

    public static function lengthDataProvider(): array
    {
        return [
            [11, '  foo bar  '],
            [1, 'f'],
            [0, ''],
            [7, 'fòô bàř'],
        ];
    }

    public static function longestCommonPrefixDataProvider(): array
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['f', 'foo bar', 'far boo'],
            ['', 'toy car', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbar', 'fòô bar'],
            ['fòô bar', 'fòô bar', 'fòô bar'],
            ['fò', 'fòô bar', 'fòr bar'],
            ['', 'toy car', 'fòô bar'],
            ['', 'fòô bar', ''],
        ];
    }

    public static function longestCommonSubstringDataProvider(): array
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['oo ', 'foo bar', 'boo far'],
            ['foo ba', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbàř', 'fòô bàř'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř'],
            [' bàř', 'fòô bàř', 'fòr bàř'],
            [' ', 'toy car', 'fòô bàř'],
            ['', 'fòô bàř', ''],
        ];
    }

    public static function longestCommonSuffixDataProvider(): array
    {
        return [
            ['bar', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['ar', 'foo bar', 'boo far'],
            ['', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['bàř', 'fòôbàř', 'fòô bàř'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř'],
            [' bàř', 'fòô bàř', 'fòr bàř'],
            ['', 'toy car', 'fòô bàř'],
            ['', 'fòô bàř', ''],
        ];
    }

    public static function lowerCaseFirstDataProvider(): array
    {
        return [
            ['test', 'Test'],
            ['test', 'test'],
            ['1a', '1a'],
            ['σ test', 'Σ test'],
            [' Σ test', ' Σ test'],
        ];
    }

    public static function offsetExistsDataProvider(): array
    {
        return [
            [true, 0],
            [true, 2],
            [false, 3],
            [true, -1],
            [true, -3],
            [false, -4],
        ];
    }

    public static function padBothDataProvider(): array
    {
        return [
            ['foo bar ', 'foo bar', 8],
            [' foo bar ', 'foo bar', 9, ' '],
            ['fòô bàř ', 'fòô bàř', 8, ' '],
            [' fòô bàř ', 'fòô bàř', 9, ' '],
            ['fòô bàř¬', 'fòô bàř', 8, '¬ø'],
            ['¬fòô bàř¬', 'fòô bàř', 9, '¬ø'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬ø'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬øÿ'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬øÿ'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ'],
        ];
    }

    public static function padLeftDataProvider(): array
    {
        return [
            ['  foo bar', 'foo bar', 9],
            ['_*foo bar', 'foo bar', 9, '_*'],
            ['_*_foo bar', 'foo bar', 10, '_*'],
            ['  fòô bàř', 'fòô bàř', 9, ' '],
            ['¬øfòô bàř', 'fòô bàř', 9, '¬ø'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø'],
            ['¬ø¬øfòô bàř', 'fòô bàř', 11, '¬ø'],
        ];
    }

    public static function padDataProvider(): array
    {
        return [
            // length <= str
            ['foo bar', 'foo bar', -1],
            ['foo bar', 'foo bar', 7],
            ['fòô bàř', 'fòô bàř', 7, ' ', 'right'],

            // right
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*', 'right'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø', 'right'],

            // left
            ['  foo bar', 'foo bar', 9, ' ', 'left'],
            ['_*foo bar', 'foo bar', 9, '_*', 'left'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø', 'left'],

            // both
            ['foo bar ', 'foo bar', 8, ' ', 'both'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø', 'both'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ', 'both'],
        ];
    }

    public static function padRightDataProvider(): array
    {
        return [
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*'],
            ['foo bar_*_', 'foo bar', 10, '_*'],
            ['fòô bàř  ', 'fòô bàř', 9, ' '],
            ['fòô bàř¬ø', 'fòô bàř', 9, '¬ø'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø'],
            ['fòô bàř¬ø¬ø', 'fòô bàř', 11, '¬ø'],
        ];
    }

    public static function prependDataProvider(): array
    {
        return [
            ['foobar', 'bar', 'foo'],
            ['fòôbàř', 'bàř', 'fòô'],
        ];
    }

    public static function regexReplaceDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['bar', 'foo', 'f[o]+', 'bar'],
            ['o bar', 'foo bar', 'f(o)o', '\1'],
            ['bar', 'foo bar', 'f[O]+\s', '', 'i'],
            ['foo', 'bar', '[[:alpha:]]{3}', 'foo'],
            ['', '', '', '', 'msr'],
            ['bàř', 'fòô ', 'f[òô]+\s', 'bàř', 'msr'],
            ['fòô', 'fò', '(ò)', '\\1ô', 'msr'],
            ['fòô', 'bàř', '[[:alpha:]]{3}', 'fòô', 'msr'],
        ];
    }

    public static function removeHtmlBreakDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof <3', 'raboof <3', '<ä>'],
            ['řàbôòf <foo<lall>>>', 'řàbôòf<br/><foo<lall>>>', ' '],
            [
                'řàb <ô>òf\', ô<br><br/>foo <a href="#">lall</a>',
                'řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>',
                '<br><br/>',
            ],
            ['<∂∆ onerror="alert(xss)">˚åß', '<∂∆ onerror="alert(xss)">' . "\n" . '˚åß'],
            ['\'œ … \'’)', '\'œ … \'’)'],
        ];
    }

    public static function removeHtmlDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof ', 'raboof <3', '<3>'],
            ['řàbôòf>', 'řàbôòf<foo<lall>>>', '<lall><lall/>'],
            ['řàb òf\', ô<br/>foo lall', 'řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>', '<br><br/>'],
            [' ˚åß', '<∂∆ onerror="alert(xss)"> ˚åß'],
            ['\'œ … \'’)', '\'œ … \'’)'],
        ];
    }

    public static function removeLeftDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['oo bar', 'foo bar', 'f'],
            ['bar', 'foo bar', 'foo '],
            ['foo bar', 'foo bar', 'oo'],
            ['foo bar', 'foo bar', 'oo bar'],
            ['oo bar', 'foo bar', Str::take('foo bar', 1)],
            ['fòô bàř', 'fòô bàř', ''],
            ['òô bàř', 'fòô bàř', 'f'],
            ['bàř', 'fòô bàř', 'fòô '],
            ['fòô bàř', 'fòô bàř', 'òô'],
            ['fòô bàř', 'fòô bàř', 'òô bàř'],
        ];
    }

    public static function removeRightDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['foo ba', 'foo bar', 'r'],
            ['foo', 'foo bar', ' bar'],
            ['foo bar', 'foo bar', 'ba'],
            ['foo bar', 'foo bar', 'foo ba'],
            ['foo ba', 'foo bar', Str::take('foo bar', -1)],
            ['fòô bàř', 'fòô bàř', ''],
            ['fòô bà', 'fòô bàř', 'ř'],
            ['fòô', 'fòô bàř', ' bàř'],
            ['fòô bàř', 'fòô bàř', 'bà'],
            ['fòô bàř', 'fòô bàř', 'fòô bà'],
        ];
    }

    public static function removeXssDataProvider(): array
    {
        return [
            ['', ''],
            [
                'Hello, i try to  your site',
                'Hello, i try to <script>alert(\'Hack\');</script> your site',
            ],
            [
                '<IMG >',
                '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>',
            ],
            ['<XSS >', '<XSS STYLE="behavior: url(xss.htc);">'],
            ['<∂∆ > ˚åß', '<∂∆ onerror="alert(xss)"> ˚åß'],
            ['\'œ … <a href="#foo"> \'’)', '\'œ … <a href="#foo"> \'’)'],
        ];
    }

    public static function emptyDataProvider(): array
    {
        return [
            [true, ''],
            [
                false,
                'Hello',
            ],
            [
                false,
                1,
            ],
            [
                false,
                1.1,
            ],
            [
                true,
                null,
            ],
        ];
    }

    public static function repeatDataProvider(): array
    {
        return [
            ['', 'foo', 0],
            ['foo', 'foo', 1],
            ['foofoo', 'foo', 2],
            ['foofoofoo', 'foo', 3],
            ['fòô', 'fòô', 1],
            ['fòôfòô', 'fòô', 2],
            ['fòôfòôfòô', 'fòô', 3],
        ];
    }

    public static function replaceAllDataProvider(): array
    {
        return [
            ['', '', [], '', true],
            ['', '', [''], '', true],
            ['foo', ' ', [' ', ''], 'foo', true],
            ['foo', '\s', ['\s', '\t'], 'foo', true],
            ['foo bar', 'foo bar', [''], '', true],
            ['\1 bar', 'foo bar', ['f(o)o', 'foo'], '\1', true],
            ['\1 \1', 'foo bar', ['foo', 'föö', 'bar'], '\1', true],
            ['bar', 'foo bar', ['foo '], '', true],
            ['far bar', 'foo bar', ['foo'], 'far', true],
            ['bar bar', 'foo bar foo bar', ['foo ', ' foo'], '', true],
            ['bar bar bar bar', 'foo bar foo bar', ['foo ', ' foo'], ['bar ', ' bar'], true],
            ['', '', [''], '', true],
            ['fòô', ' ', [' ', '', '  '], 'fòô', true],
            ['fòôòô', '\s', ['\s', 'f'], 'fòô', true],
            ['fòô bàř', 'fòô bàř', [''], '', true],
            ['bàř', 'fòô bàř', ['fòô '], '', true],
            ['far bàř', 'fòô bàř', ['fòô'], 'far', true],
            ['bàř bàř', 'fòô bàř fòô bàř', ['fòô ', 'fòô'], '', true],
            ['', '', [''], '', false],
            ['fòô', ' ', [' ', '', '  '], 'fòô', false],
            ['fòôòô', '\s', ['\s', 'f'], 'fòô', false],
            ['fòô bàř', 'fòô bàř', [''], '', false],
            ['bàř', 'fòô bàř', ['fòÔ '], '', false],
            ['bàř', 'fòô bàř', ['fòÔ '], [''], false],
            ['far bàř', 'fòô bàř', ['Fòô'], 'far', false],
        ];
    }

    public static function replaceBeginningDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', '', '', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar foo bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', ''],
            ['fòô', '', '', 'fòô'],
            ['fòô', '\s', '\s', 'fòô'],
            ['fòô bàř', 'fòô bàř', '', ''],
            ['bàř', 'fòô bàř', 'fòô ', ''],
            ['far bàř', 'fòô bàř', 'fòô', 'far'],
            ['bàř fòô bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
        ];
    }

    public static function replaceFirstDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foofoofoo', 'foofoo', 'foo', 'foofoo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar foo bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', ''],
            ['fòô', '\s', '\s', 'fòô'],
            ['fòô bàř', 'fòô bàř', '', ''],
            ['bàř', 'fòô bàř', 'fòô ', ''],
            ['fòô bàř', 'fòô fòô bàř', 'fòô ', ''],
            ['far bàř', 'fòô bàř', 'fòô', 'far'],
            ['bàř fòô bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
        ];
    }

    public static function replaceLastDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foofoofoo', 'foofoo', 'foo', 'foofoo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['foo lall', 'foo bar', 'bar', 'lall'],
            ['foo bar foo ', 'foo bar foo bar', 'bar', ''],
            ['', '', '', ''],
            ['fòô', '\s', '\s', 'fòô'],
            ['fòô bàř', 'fòô bàř', '', ''],
            ['fòô', 'fòô bàř', ' bàř', ''],
            ['fòôfar', 'fòô bàř', ' bàř', 'far'],
            ['fòô bàř fòô', 'fòô bàř fòô bàř', ' bàř', ''],
        ];
    }

    public static function replaceEndingDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', '', '', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['foo bar', 'foo bar', 'foo', '\1'],
            ['foo bar', 'foo bar', 'foo ', ''],
            ['foo lall', 'foo bar', 'bar', 'lall'],
            ['foo bar foo ', 'foo bar foo bar', 'bar', ''],
            ['', '', '', ''],
            ['fòô', '', '', 'fòô'],
            ['fòô', '\s', '\s', 'fòô'],
            ['fòô bàř', 'fòô bàř', '', ''],
            ['fòô', 'fòô bàř', ' bàř', ''],
            ['fòôfar', 'fòô bàř', ' bàř', 'far'],
            ['fòô bàř fòô', 'fòô bàř fòô bàř', ' bàř', ''],
        ];
    }

    public static function replaceDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', ' ', ' ', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', ''],
            ['fòô', ' ', ' ', 'fòô'],
            ['fòô', '\s', '\s', 'fòô'],
            ['fòô bàř', 'fòô bàř', '', ''],
            ['bàř', 'fòô bàř', 'fòô ', ''],
            ['far bàř', 'fòô bàř', 'fòô', 'far'],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', 'Fòô ', ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', 'fòÔ ', ''],
            ['fòô bàř bàř', 'fòô bàř [[fòô]] bàř', '[[fòô]] ', ''],
            ['', '', '', '', false],
            ['òô', ' ', ' ', 'òô', false],
            ['fòô', '\s', '\s', 'fòô', false],
            ['fòô bàř', 'fòô bàř', '', '', false],
            ['bàř', 'fòô bàř', 'Fòô ', '', false],
            ['far bàř', 'fòô bàř', 'fòÔ', 'far', false],
            ['bàř bàř', 'fòô bàř fòô bàř', 'Fòô ', '', false],
        ];
    }

    public static function reverseDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof', 'foobar'],
            ['řàbôòf', 'fòôbàř'],
            ['řàb ôòf', 'fòô bàř'],
            ['∂∆ ˚åß', 'ßå˚ ∆∂'],
        ];
    }

    public static function safeTruncateDataProvider(): array
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test', 'Testfoobar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['Test....', 'Test foo bar', 11, '....'],
            ['Test fòô bàř', 'Test fòô bàř', 12, ''],
            ['Test fòô', 'Test fòô bàř', 11, ''],
            ['Test fòô', 'Test fòô bàř', 8, ''],
            ['Test', 'Test fòô bàř', 7, ''],
            ['Test', 'Test fòô bàř', 4, ''],
            ['Test fòô bàř', 'Test fòô bàř', 12, 'ϰϰ'],
            ['Test fòôϰϰ', 'Test fòô bàř', 11, 'ϰϰ'],
            ['Testϰϰ', 'Test fòô bàř', 8, 'ϰϰ'],
            ['Testϰϰ', 'Test fòô bàř', 7, 'ϰϰ'],
            ['What are your plans...', 'What are your plans today?', 22, '...'],
        ];
    }

    public static function shortenAfterWordDataProvider(): array
    {
        return [
            ['this...', 'this is a test', 5, '...'],
            ['this is...', 'this is öäü-foo test', 8, '...'],
            ['fòô', 'fòô bàř fòô', 6, ''],
            ['fòô bàř', 'fòô bàř fòô', 8, ''],
        ];
    }

    public static function shuffleDataProvider(): array
    {
        return [
            ['foo bar'],
            ['∂∆ ˚åß'],
            ['å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬'],
        ];
    }

    public static function sliceDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 0],
            ['foobar', 'foobar', 0, null],
            ['foobar', 'foobar', 0, 6],
            ['fooba', 'foobar', 0, 5],
            ['', 'foobar', 3, 0],
            ['', 'foobar', 3, 2],
            ['ba', 'foobar', 3, 5],
            ['ba', 'foobar', 3, -1],
            ['fòôbàř', 'fòôbàř', 0, null],
            ['fòôbàř', 'fòôbàř', 0, null],
            ['fòôbàř', 'fòôbàř', 0, 6],
            ['fòôbà', 'fòôbàř', 0, 5],
            ['', 'fòôbàř', 3, 0],
            ['', 'fòôbàř', 3, 2],
            ['bà', 'fòôbàř', 3, 5],
            ['bà', 'fòôbàř', 3, -1],
        ];
    }

    public static function slugifyDataProvider(): array
    {
        return [
            ['foo-bar', 'foo bar'],
        ];
    }

    public static function snakeizeDataProvider(): array
    {
        return [
            ['snake_case', 'SnakeCase'],
            ['snake_case', 'Snake-Case'],
            ['snake_case', 'snake case'],
            ['snake_case', 'snake -case'],
            ['snake_case', 'snake - case'],
            ['snake_case', 'snake_case'],
            ['camel_c_test', 'camel c test'],
            ['string_with_1_number', 'string_with 1 number'],
            ['string_with_1_number', 'string_with1number'],
            ['string_with_2_2_numbers', 'string-with-2-2 numbers'],
            ['data_rate', 'data_rate'],
            ['background_color', 'background-color'],
            ['yes_we_can', 'yes_we_can'],
            ['moz_something', '-moz-something'],
            ['car_speed', '_car_speed_'],
            ['serve_h_t_t_p', 'ServeHTTP'],
            ['1_camel_2_case', '1camel2case'],
            ['camel_σase', 'camel σase'],
            ['στανιλ_case', 'Στανιλ case'],
            ['σamel_case', 'σamel  Case'],
        ];
    }

    public static function startsWithDataProvider(): array
    {
        return [
            [true, 'foo bars', 'foo bar'],
            [true, 'FOO bars', 'foo bar', false],
            [true, 'FOO bars', 'foo BAR', false],
            [true, 'FÒÔ bàřs', 'fòô bàř', false],
            [true, 'fòô bàřs', 'fòô BÀŘ', false],
            [false, 'foo bar', 'bar'],
            [false, 'foo bar', 'foo bars'],
            [false, 'FOO bar', 'foo bars'],
            [false, 'FOO bars', 'foo BAR'],
            [false, 'FÒÔ bàřs', 'fòô bàř', true],
            [false, 'fòô bàřs', 'fòô BÀŘ', true],
        ];
    }

    public static function startsWithAnyDataProvider(): array
    {
        return [
            [true, 'foo bars', ['foo bar']],
            [true, 'foo bars', ['foo', 'bar']],
            [true, 'FOO bars', ['foo', 'bar'], false],
            [true, 'FOO bars', ['foo', 'BAR'], false],
            [true, 'FÒÔ bàřs', ['fòô', 'bàř'], false],
            [true, 'fòô bàřs', ['fòô BÀŘ'], false],
            [false, 'foo bar', ['bar']],
            [false, 'foo bar', ['foo bars']],
            [false, 'FOO bar', ['foo bars']],
            [false, 'FOO bars', ['foo BAR']],
            [false, 'FÒÔ bàřs', ['fòô bàř'], true],
            [false, 'fòô bàřs', ['fòô BÀŘ'], true],
        ];
    }

    public static function stripWhitespaceDataProvider(): array
    {
        return [
            ['foobar', '  foo   bar  '],
            ['teststring', 'test string'],
            ['Οσυγγραφέας', '   Ο     συγγραφέας  '],
            ['123', ' 123 '],
            ['', ' '], // no-break space (U+00A0)
            ['', '           '], // spaces U+2000 to U+200A
            ['', ' '], // narrow no-break space (U+202F)
            ['', ' '], // medium mathematical space (U+205F)
            ['', '　'], // ideographic space (U+3000)
            ['123', '  1  2  3　　'],
            ['', ' '],
            ['', ''],
        ];
    }

    public static function substrDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', 0],
            ['bar', 'foo bar', 4],
            ['bar', 'foo bar', 4, null],
            ['o b', 'foo bar', 2, 3],
            ['', 'foo bar', 4, 0],
            ['fòô bàř', 'fòô bàř', 0, null],
            ['bàř', 'fòô bàř', 4, null],
            ['ô b', 'fòô bàř', 2, 3],
            ['', 'fòô bàř', 4, 0],
        ];
    }

    public static function surroundDataProvider(): array
    {
        return [
            ['__foobar__', 'foobar', '__'],
            ['test', 'test', ''],
            ['**', '', '*'],
            ['¬fòô bàř¬', 'fòô bàř', '¬'],
            ['ßå∆˚ test ßå∆˚', ' test ', 'ßå∆˚'],
        ];
    }

    public static function swapCaseDataProvider(): array
    {
        return [
            ['TESTcASE', 'testCase'],
            ['tEST-cASE', 'Test-Case'],
            [' - σASH  cASE', ' - Σash  Case'],
            ['νΤΑΝΙΛ', 'Ντανιλ'],
        ];
    }

    public static function tidyDataProvider(): array
    {
        return [
            ['"I see..."', '“I see…”'],
            ["'This too'", '‘This too’'],
            ['test-dash', 'test—dash'],
            ['Ο συγγραφέας είπε...', 'Ο συγγραφέας είπε…'],
        ];
    }

    public static function titleizeDataProvider(): array
    {
        return [
            ['Title Case', 'TITLE CASE'],
            ['Testing the Method', 'testing the method'],
            ['Testing the method With Ignored Word', 'testing the method with ignored word', ['method']],
            ['Θα Ήθελα Να Φύγει', '  Θα ήθελα να φύγει  '],
        ];
    }

    public static function toTransliterateDataProvider(): array
    {
        return [
            ['foo bar', 'fòô bàř'],
            [' TEST ', ' ŤÉŚŢ '],
            ['ph = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['lysaia gora', 'лысая гора'],
            ['shchuka', 'щука'],
            ['Han Zi ', '漢字'],
            ['xin chao the gioi', 'xin chào thế giới'],
            ['XIN CHAO THE GIOI', 'XIN CHÀO THẾ GIỚI'],
            ['dam phat chet luon', 'đấm phát chết luôn'],
            [' ', ' '], // no-break space (U+00A0)
            ['           ', '           '], // spaces U+2000 to U+200A
            [' ', ' '], // narrow no-break space (U+202F)
            [' ', ' '], // medium mathematical space (U+205F)
            [' ', '　'], // ideographic space (U+3000)
            ['?', '𐍉'], // some uncommon, unsupported character (U+10349)
        ];
    }

    public static function toBooleanDataProvider(): array
    {
        return [
            [true, 'true'],
            [true, '1'],
            [true, 'on'],
            [true, 'ON'],
            [true, 'yes'],
            [false, '999'],
            [false, 'false'],
            [false, '0'],
            [false, 'off'],
            [false, 'OFF'],
            [false, 'no'],
            [false, '-999'],
            [false, ''],
            [false, ' '],
            [false, '  '], // narrow no-break space (U+202F)
        ];
    }

    public static function toSpacesDataProvider(): array
    {
        return [
            ['    foo    bar    ', '	foo	bar	'],
            ['     foo     bar     ', '	foo	bar	', 5],
            ['    foo  bar  ', '		foo	bar	', 2],
            ['foobar', '	foo	bar	', 0],
            ["    foo\n    bar", "	foo\n	bar"],
            ["    fòô\n    bàř", "	fòô\n	bàř"],
        ];
    }

    public static function toTabsDataProvider(): array
    {
        return [
            ['	foo	bar	', '    foo    bar    '],
            ['	foo	bar	', '     foo     bar     ', 5],
            ['		foo	bar	', '    foo  bar  ', 2],
            ["	foo\n	bar", "    foo\n    bar"],
            ["	fòô\n	bàř", "    fòô\n    bàř"],
        ];
    }

    public static function trimLeftDataProvider(): array
    {
        return [
            ['foo   bar  ', '  foo   bar  '],
            ['foo bar', ' foo bar'],
            ['foo bar ', 'foo bar '],
            ["foo bar \n\t", "\n\t foo bar \n\t"],
            ['fòô   bàř  ', '  fòô   bàř  '],
            ['fòô bàř', ' fòô bàř'],
            ['fòô bàř ', 'fòô bàř '],
            ['foo bar', '--foo bar', '-'],
            ['fòô bàř', 'òòfòô bàř', 'ò'],
            ["fòô bàř \n\t", "\n\t fòô bàř \n\t", null],
            ['fòô ', ' fòô ', null], // narrow no-break space (U+202F)
            ['fòô  ', '  fòô  ', null], // medium mathematical space (U+205F)
            ['fòô', '           fòô', null], // spaces U+2000 to U+200A
        ];
    }

    public static function trimRightDataProvider(): array
    {
        return [
            ['  foo   bar', '  foo   bar  '],
            ['foo bar', 'foo bar '],
            [' foo bar', ' foo bar'],
            ["\n\t foo bar", "\n\t foo bar \n\t"],
            ['  fòô   bàř', '  fòô   bàř  '],
            ['fòô bàř', 'fòô bàř '],
            [' fòô bàř', ' fòô bàř'],
            ['foo bar', 'foo bar--', '-'],
            ['fòô bàř', 'fòô bàřòò', 'ò'],
            ["\n\t fòô bàř", "\n\t fòô bàř \n\t", null],
            [' fòô', ' fòô ', null], // narrow no-break space (U+202F)
            ['  fòô', '  fòô  ', null], // medium mathematical space (U+205F)
            ['fòô', 'fòô           ', null], // spaces U+2000 to U+200A
        ];
    }

    public static function truncateDataProvider(): array
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo ba', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test fo', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test ...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['T...', 'Test foo bar', 4, '...'],
            ['Test fo....', 'Test foo bar', 11, '....'],
            ['Test fòô bàř', 'Test fòô bàř', 12, ''],
            ['Test fòô bà', 'Test fòô bàř', 11, ''],
            ['Test fòô', 'Test fòô bàř', 8, ''],
            ['Test fò', 'Test fòô bàř', 7, ''],
            ['Test', 'Test fòô bàř', 4, ''],
            ['Test fòô bàř', 'Test fòô bàř', 12, 'ϰϰ'],
            ['Test fòô ϰϰ', 'Test fòô bàř', 11, 'ϰϰ'],
            ['Test fϰϰ', 'Test fòô bàř', 8, 'ϰϰ'],
            ['Test ϰϰ', 'Test fòô bàř', 7, 'ϰϰ'],
            ['Teϰϰ', 'Test fòô bàř', 4, 'ϰϰ'],
            ['What are your pl...', 'What are your plans today?', 19, '...'],
        ];
    }

    public static function underscoredDataProvider(): array
    {
        return [
            ['test_case', 'testCase'],
            ['test_case', 'Test-Case'],
            ['test_case', 'test case'],
            ['test_case', 'test -case'],
            ['_test_case', '-test - case'],
            ['test_case', 'test_case'],
            ['test_c_test', '  test c test'],
            ['test_u_case', 'TestUCase'],
            ['test_c_c_test', 'TestCCTest'],
            ['string_with1number', 'string_with1number'],
            ['string_with_2_2_numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['yes_we_can', 'yesWeCan'],
            ['test_σase', 'test Σase'],
            ['στανιλ_case', 'Στανιλ case'],
            ['σash_case', 'Σash  Case'],
        ];
    }

    public static function upperCamelizeDataProvider(): array
    {
        return [
            ['CamelCase', 'camelCase'],
            ['CamelCase', 'Camel-Case'],
            ['CamelCase', 'camel case'],
            ['CamelCase', 'camel -case'],
            ['CamelCase', 'camel - case'],
            ['CamelCase', 'camel_case'],
            ['CamelCTest', 'camel c test'],
            ['StringWith1number', 'string_with1number'],
            ['StringWith22Numbers', 'string-with-2-2 numbers'],
            ['1camel2case', '1camel2case'],
            ['CamelΣase', 'camel σase'],
            ['ΣτανιλCase', 'στανιλ case'],
            ['ΣamelCase', 'Σamel  Case'],
        ];
    }

    public static function strBeginsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, '0123こ', true, 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'EUC-JP'],
            [$euc_jp, '0123', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'EUC-JP'],
            [$string_ascii, 'a', true, 'ISO-8859-1'],
            [$string_ascii, 'A', false, 'ISO-8859-1'],
            [$string_ascii, 'b', false, 'ISO-8859-1'],
            [$string_ascii, '', true, 'ISO-8859-1'],
            [$string_ascii, 'abc', true, null],
            [$string_ascii, 'bc', false, null],
            [$string_ascii, '', true, null],
            [$string_mb, base64_decode('5pel5pys6Kqe', true), true, null],
            [$string_mb, base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, null],
            [$string_mb, '', true, null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ΤῊ', false, null],
        ];
    }

    public static function strEndsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, 'い。', true, 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'EUC-JP'],
            [$euc_jp, 'い。', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'EUC-JP'],
            [$string_ascii, 'f', true, 'ISO-8859-1'],
            [$string_ascii, 'F', false, 'ISO-8859-1'],
            [$string_ascii, 'e', false, 'ISO-8859-1'],
            [$string_ascii, '', true, 'ISO-8859-1'],
            [$string_ascii, 'def', true, null],
            [$string_ascii, 'de', false, null],
            [$string_ascii, '', true, null],
            [$string_mb, base64_decode('77yZ44CC', true), true, null],
            [$string_mb, base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, null],
            [$string_mb, '', true, null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ἙΛΛΗΝΙΚῊ', false, null],
        ];
    }

    public static function strIbeginsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, '0123こ', true, 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'EUC-JP'],
            [$euc_jp, '0123', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'EUC-JP'],
            [$string_ascii, 'a', true, 'ISO-8859-1'],
            [$string_ascii, 'A', true, 'ISO-8859-1'],
            [$string_ascii, 'b', false, 'ISO-8859-1'],
            [$string_ascii, '', true, 'ISO-8859-1'],
            [$string_ascii, 'abc', true, null],
            [$string_ascii, 'AbC', true, null],
            [$string_ascii, 'bc', false, null],
            [$string_ascii, '', true, null],
            [$string_mb, base64_decode('5pel5pys6Kqe', true), true, null],
            [$string_mb, base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, null],
            [$string_mb, '', true, null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ΤῊ', true, null],
        ];
    }

    public static function strIendsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, 'い。', true, 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'EUC-JP'],
            [$euc_jp, 'い。', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'EUC-JP'],
            [$string_ascii, 'f', true, 'ISO-8859-1'],
            [$string_ascii, 'F', true, 'ISO-8859-1'],
            [$string_ascii, 'e', false, 'ISO-8859-1'],
            [$string_ascii, '', true, 'ISO-8859-1'],
            [$string_ascii, 'def', true, null],
            [$string_ascii, 'DeF', true, null],
            [$string_ascii, 'de', false, null],
            [$string_ascii, '', true, null],
            [$string_mb, base64_decode('77yZ44CC', true), true, null],
            [$string_mb, base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, null],
            [$string_mb, '', true, null],
            // ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ἙΛΛΗΝΙΚῊ', true, null], // php 7.3 thingy
        ];
    }

    public static function titleizeForHumansDataProvider(): array
    {
        return [
            ['Title Case', 'TITLE CASE'],
            ['Testing the Method', 'testing the method'],
            ['I Like to watch DVDs at Home', 'i like to watch DVDs at home', ['watch']],
            ['Θα Ήθελα Να Φύγει', '  Θα ήθελα να φύγει  ', []],
            [
                'For Step-by-Step Directions Email someone@gmail.com',
                'For step-by-step directions email someone@gmail.com',
            ],
            [
                "2lmc Spool: 'Gruber on OmniFocus and Vapo(u)rware'",
                "2lmc Spool: 'Gruber on OmniFocus and Vapo(u)rware'",
            ],
            ['Have You Read “The Lottery”?', 'Have you read “The Lottery”?'],
            ['Your Hair[cut] Looks (Nice)', 'your hair[cut] looks (nice)'],
            [
                "People Probably Won't Put http://foo.com/bar/ in Titles",
                "People probably won't put http://foo.com/bar/ in titles",
            ],
            [
                'Scott Moritz and TheStreet.com’s Million iPhone La‑La Land',
                'Scott Moritz and TheStreet.com’s million iPhone la‑la land',
            ],
            ['BlackBerry vs. iPhone', 'BlackBerry vs. iPhone'],
            [
                'Notes and Observations Regarding Apple’s Announcements From ‘The Beat Goes On’ Special Event',
                'Notes and observations regarding Apple’s announcements from ‘The Beat Goes On’ special event',
            ],
            [
                'Read markdown_rules.txt to Find Out How _Underscores Around Words_ Will Be Interpreted',
                'Read markdown_rules.txt to find out how _underscores around words_ will be interpreted',
            ],
            [
                "Q&A With Steve Jobs: 'That's What Happens in Technology'",
                "Q&A with Steve Jobs: 'That's what happens in technology'",
            ],
            ["What Is AT&T's Problem?", "What is AT&T's problem?"],
            ['Apple Deal With AT&T Falls Through', 'Apple deal with AT&T falls through'],
            ['This v That', 'this v that'],
            ['This vs That', 'this vs that'],
            ['This v. That', 'this v. that'],
            ['This vs. That', 'this vs. that'],
            ["The SEC's Apple Probe: What You Need to Know", "The SEC's Apple probe: what you need to know"],
            [
                "'By the Way, Small Word at the Start but Within Quotes.'",
                "'by the way, small word at the start but within quotes.'",
            ],
            ['Small Word at End Is Nothing to Be Afraid Of', 'Small word at end is nothing to be afraid of'],
            [
                'Starting Sub-Phrase With a Small Word: A Trick, Perhaps?',
                'Starting sub-phrase with a small word: a trick, perhaps?',
            ],
            [
                "Sub-Phrase With a Small Word in Quotes: 'A Trick, Perhaps?'",
                "Sub-phrase with a small word in quotes: 'a trick, perhaps?'",
            ],
            [
                'Sub-Phrase With a Small Word in Quotes: "A Trick, Perhaps?"',
                'Sub-phrase with a small word in quotes: "a trick, perhaps?"',
            ],
            ['"Nothing to Be Afraid Of?"', '"Nothing to Be Afraid of?"'],
            ['A Thing', 'a thing'],
            [
                'Dr. Strangelove (Or: How I Learned to Stop Worrying and Love the Bomb)',
                'Dr. Strangelove (or: how I Learned to Stop Worrying and Love the Bomb)',
            ],
            ['This Is Trimming', '  this is trimming'],
            ['This Is Trimming', 'this is trimming  '],
            ['This Is Trimming', '  this is trimming  '],
            ['If It’s All Caps, Fix It', 'IF IT’S ALL CAPS, FIX IT'],
            ['What Could/Should Be Done About Slashes?', 'What could/should be done about slashes?'],
            [
                'Never Touch Paths Like /var/run Before/After /boot',
                'Never touch paths like /var/run before/after /boot',
            ],
        ];
    }

    public static function beforeFirstDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['foo<h1></h1>', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['</', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            [' ', ' b<b></b>', 'b', true],
            ['<', '<b><b>lall</b>', 'b', true],
            ['</', '</b>lall</b>', 'b', true],
            ['[', '[b][/b]', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['κόσμ', 'κόσμbε ¡-öäü', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['foo<h1></h1>', 'foo<h1></h1>Bar', 'b', false],
            ['foo<h1></h1>', 'foo<h1></h1>bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['</', '</b></b>', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            [' ', ' b<b></b>', 'b', false],
            ['<', '<b><b>lall</b>', 'b', false],
            ['</', '</b>lall</b>', 'b', false],
            ['[', '[B][/B]', 'b', false],
            ['κόσμ', 'κόσμbε ¡-öäü', 'b', false],
            ['', 'Bκόσμbε', 'b', false],
        ];
    }

    public static function beforeLastDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['foo<h1></h1>', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['</b></', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            [' b<b></', ' b<b></b>', 'b', true],
            ['<b><b>lall</', '<b><b>lall</b>', 'b', true],
            ['</b>lall</', '</b>lall</b>', 'b', true],
            ['[b][/', '[b][/b]', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['κόσμ', 'κόσμbε ¡-öäü', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['foo<h1></h1>', 'foo<h1></h1>Bar', 'b', false],
            ['foo<h1></h1>', 'foo<h1></h1>bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['</b></', '</b></b>', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            [' b<b></', ' b<b></b>', 'b', false],
            ['<b><b>lall</', '<b><b>lall</b>', 'b', false],
            ['</b>lall</', '</b>lall</b>', 'b', false],
            ['[B][/', '[B][/B]', 'b', false],
            ['κόσμ', 'κόσμbε ¡-öäü', 'b', false],
            ['bκόσμ', 'bκόσμbε', 'b', false],
        ];
    }

    public static function idnToUtf8EmailDataProvider(): array
    {
        return [
            ['userName', 'userName'],
            ['aaa@äö.ee', 'aaa@xn--4ca0b.ee'],
        ];
    }

    public static function emojiToShortcodesDataProvider(): array
    {
        return [
            ['Baby you light my :fire:! :smiley:', 'Baby you light my 🔥! 😃'],
            ['Test — em – en - dashes :hand_with_index_and_middle_fingers_crossed:', 'Test — em – en - dashes 🤞'],
        ];
    }

    public static function shortcodesToEmojiDataProvider(): array
    {
        return [
            ['Baby you light my 🔥! 😃', 'Baby you light my :fire:! :smiley:'],
            ['Test — em – en - dashes 🤞', 'Test — em – en - dashes :hand_with_index_and_middle_fingers_crossed:'],
        ];
    }

    public static function escapeShortcodesDataProvider(): array
    {
        return [
            ['\\:100\\: \\:1234\\: 🔥', ':100: :1234: 🔥'],
        ];
    }

    public static function unescapeShortcodesDataProvider(): array
    {
        return [
            [':100: :1234: 🔥', '\\:100\\: \\:1234\\: 🔥'],
        ];
    }
}
