<?php

namespace Craft\Cms\Support;

use BackedEnum;
use craft\helpers\App;
use InvalidArgumentException;
use LitEmoji\LitEmoji;
use Ramsey\Uuid\Validator\GenericValidator;
use voku\helper\ASCII;

class Str extends \Illuminate\Support\Str
{
    /**
     * @var array Character mappings
     * @see asciiCharMap()
     */
    private static array $_asciiCharMaps;

    /**
     * Get the first n characters from the string.
     *
     * @param  string  $string
     * @param  int  $length
     * @param string $encoding
     *
     * @return string
     */
    public static function first(string $string, int $length = 1, string $encoding = 'UTF-8'): string
    {
        return static::substr($string, 0, $length, $encoding);
    }

    /**
     * Get the last n characters from the string.
     *
     * @param  string  $string
     * @param  int  $length
     * @param string $encoding
     *
     * @return string
     */
    public static function last(string $string, int $length = 1, string $encoding = 'UTF-8'): string
    {
        return static::substr($string, -1, $length, $encoding);
    }

    public static function uuidPattern(): string
    {
        return (new GenericValidator())->getPattern();
    }

    /**
     * Converts an object to its string representation. If the object is an array, will glue the array elements together
     * with the $glue param. Otherwise will cast the object to a string.
     *
     * @param mixed $object The object to convert to a string.
     * @param string $glue The glue to use if the object is an array.
     * @return string The string representation of the object.
     */
    public static function toString(mixed $object, string $glue = ','): string
    {
        if (is_scalar($object) || (is_object($object) && method_exists($object, '__toString'))) {
            return (string) $object;
        }

        if ($object instanceof BackedEnum) {
            return $object->value;
        }

        if (is_iterable($object)) {
            $stringValues = [];

            foreach ($object as $value) {
                if (($value = static::toString($value, $glue)) !== '') {
                    $stringValues[] = $value;
                }
            }

            return implode($glue, $stringValues);
        }

        return '';
    }

    /**
     * Returns ASCII character mappings, merging in any custom defined mappings
     * from the <config5:customAsciiCharMappings> config setting.
     *
     * @param bool $flat Whether the mappings should be returned as a flat array (é => e)
     * @param string|null $language Whether to include language-specific mappings (only applied if $flat is true)
     * @return array The fully merged ASCII character mappings.
     */
    public static function asciiCharMap(bool $flat = false, ?string $language = '*'): array
    {
        $key = $flat ? "flat-$language" : '*';

        if (isset(self::$_asciiCharMaps[$key])) {
            return self::$_asciiCharMaps[$key];
        }

        $map = ASCII::charsArrayWithSingleLanguageValues(false, false);

        if ($language !== null) {
            /** @var ASCII::*_LANGUAGE_CODE $language */
            $langSpecific = ASCII::charsArrayWithOneLanguage($language, false, false);

            if ($langSpecific !== []) {
                $map = array_merge($map, $langSpecific);
            }
        }

        if ($flat) {
            return self::$_asciiCharMaps[$key] = $map;
        }

        $byAscii = [];

        foreach ($map as $char => $ascii) {
            $byAscii[$ascii][] = $char;
        }

        return self::$_asciiCharMaps[$key] = $byAscii;
    }

    /**
     * Returns a handle-safe version of a string.
     *
     * @param string $str
     * @return string
     * @since 6.0.0
     */
    public static function toHandle(string $str): string
    {
        // Remove HTML tags
        $handle = strip_tags($str);

        // Remove inner-word punctuation
        $handle = preg_replace('/[\'"‘’“”ʻ\[\](){}:]/u', '', $handle);

        // Make it lowercase
        $handle = static::lower($handle);

        // Convert extended ASCII characters to basic ASCII
        $handle = static::ascii($handle);

        // Handle must start with a letter
        $handle = preg_replace('/^[^a-z]+/', '', $handle);

        // Replace any remaining non-alphanumeric or underscore characters with spaces
        $handle = preg_replace('/[^a-z0-9_]/', ' ', $handle);

        return static::camel($handle);
    }

    /**
     * Converts an email from IDNA ASCII to Unicode, if the server supports IDNA ASCII strings.
     *
     * @param string $email
     * @return string
     * @since 6.0.0
     */
    public static function idnToUtf8Email(string $email): string
    {
        if (! App::supportsIdn()) {
            return $email;
        }

        $parts = explode('@', $email, 2);

        foreach ($parts as &$part) {
            if (($part = idn_to_utf8($part, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)) === false) {
                return $email;
            }
        }

        $combined = implode('@', $parts);

        // Return the original string if nothing changed besides casing
        if (strcasecmp($combined, $email) === 0) {
            return $email;
        }

        return $combined;
    }

    /**
     * Converts shortcodes to emoji.
     *
     * @param string $str
     * @return string
     * @since 6.0.0
     */
    public static function shortcodesToEmoji(string $str): string
    {
        return LitEmoji::shortcodeToUnicode($str);
    }

    /**
     * Converts emoji to shortcodes.
     *
     * @param string $str
     * @return string
     * @since 6.0.0
     */
    public static function emojiToShortcodes(string $str): string
    {
        // Add delimiters around all 4-byte chars
        $dl = '__MB4_DL__';
        $dr = '__MB4_DR__';

        $str = static::replaceMb4($str, fn($char) => sprintf('%s%s%s', $dl, $char, $dr));

        // Strip out consecutive delimiters
        $str = str_replace(sprintf('%s%s', $dr, $dl), '', $str);

        // Replace all 4-byte sequences individually
        return preg_replace_callback("/$dl(.+?)$dr/", fn($m) => LitEmoji::unicodeToShortcode($m[1]), $str);
    }

    /**
     * Replaces 4-byte UTF-8 characters in a string.
     * ---
     * ```php
     * // Convert emojis to smilies
     * $string = StringHelper::replaceMb4($string, function($char) {
     *     switch ($char) {
     *         case '😀':
     *             return ':)';
     *         case '☹️':
     *             return ':(';
     *         default:
     *             return '¯\_(ツ)_/¯';
     *     }
     * });
     * ```
     *
     * @param string $str The string
     * @param callable|string $replace The replacement string, or callback function.
     * @return string The string with converted 4-byte UTF-8 characters
     * @since 6.0.0
     */
    public static function replaceMb4(string $str, callable|string $replace): string
    {
        $r = preg_replace_callback('/./u', function(array $match) use ($replace): string {
            if (strlen($match[0]) >= 4) {
                return is_callable($replace) ? $replace($match[0]) : $replace;
            }

            return $match[0];
        }, $str);

        if ($r === null) {
            $message = match (preg_last_error()) {
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
                default => 'Invalid string',
            };

            throw new InvalidArgumentException($message);
        }

        return $r;
    }

    /**
     * Returns a regex pattern for invisible characters.
     *
     * @return string
     * @since 6.0.0
     */
    public static function invisibleCharsPattern(): string
    {
        $invisibleCharCodes = [
            '00ad', // soft hyphen
            '0083', // no break
            '200b', // zero width space
            '200c', // zero width non-joiner
            '200d', // zero width joiner
            '200e', // LTR character
            '200f', // RTL character
            '2062', // invisible times
            '2063', // invisible comma
            '2064', // invisible plus
            'feff', //zero width non-break space
        ];

        array_walk(
            $invisibleCharCodes,
            fn(&$charCode) => $charCode = sprintf('\\x{%s}', $charCode)
        );

        return sprintf('/%s/iu', implode('|', $invisibleCharCodes));
    }
}
