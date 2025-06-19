<?php

namespace Craft\Cms\Support;

use BackedEnum;
use craft\helpers\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LitEmoji\LitEmoji;
use Ramsey\Uuid\Validator\GenericValidator;
use voku\helper\ASCII;

class Str extends \Illuminate\Support\Str
{
    /** @see asciiCharMap() */
    private static ?array $asciiCharMaps = null;

    /** @see escapeShortcodes() */
    private static array|false|null $shortcodeEscapeMap = null;

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

        if (isset(self::$asciiCharMaps[$key])) {
            return self::$asciiCharMaps[$key];
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
            return self::$asciiCharMaps[$key] = $map;
        }

        $byAscii = [];

        foreach ($map as $char => $ascii) {
            $byAscii[$ascii][] = $char;
        }

        return self::$asciiCharMaps[$key] = $byAscii;
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
     * Escapes shortcodes.
     *
     * @param string $str
     * @return string
     * @since 4.5.0
     */
    public static function escapeShortcodes(string $str): string
    {
        $map = self::shortcodeEscapeMap();

        if ($map === false) {
            return $str;
        }

        return str_replace(array_keys($map), $map, $str);
    }

    /**
     * Unscapes shortcodes.
     *
     * @param string $str
     * @return string
     * @since 4.5.0
     */
    public static function unescapeShortcodes(string $str): string
    {
        $map = self::shortcodeEscapeMap();

        if ($map === false) {
            return $str;
        }

        return str_replace($map, array_keys($map), $str);
    }

    private static function shortcodeEscapeMap(): array|false
    {
        if (isset(self::$shortcodeEscapeMap)) {
            return self::$shortcodeEscapeMap;
        }

        $path = base_path('vendor/elvanto/litemoji/src/shortcodes-array.php');

        if (! file_exists($path)) {
            Log::warning('Unable to escape shortcodes: shortcodes-array.php doesn’t exist at the expected location.');
            return self::$shortcodeEscapeMap = false;
        }

        $shortcodes = array_keys(require $path);

        self::$shortcodeEscapeMap = array_combine(
            array_map(fn(string $shortcode) => ":$shortcode:", $shortcodes),
            array_map(fn(string $shortcode) => "\\:$shortcode\\:", $shortcodes),
        );

        return self::$shortcodeEscapeMap;
    }

    /**
     * Detects whether the given string has any 4-byte UTF-8 characters.
     *
     * @param string $str The string to process.
     * @return bool Whether the string contains any 4-byte UTF-8 characters or not.
     */
    public static function containsMb4(string $str): bool
    {
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            if (ord($str[$i]) >= 240) {
                return true;
            }
        }

        return false;
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
     * HTML-encodes any 4-byte UTF-8 characters.
     *
     * @param string $str The string
     * @return string The string with converted 4-byte UTF-8 characters
     * @see http://stackoverflow.com/a/16496730/1688568
     */
    public static function encodeMb4(string $str): string
    {
        // (Logic pulled from WP's wp_encode_emoji() function)
        // UTF-32's hex encoding is the same as HTML's hex encoding.
        // So, by converting from UTF-8 to UTF-32, we magically
        // get the correct hex encoding.
        return Str::replaceMb4($str, static function($char) {
            $unpacked = unpack('H*', mb_convert_encoding($char, 'UTF-32', 'UTF-8'));
            return isset($unpacked[1]) ? '&#x' . ltrim($unpacked[1], '0') . ';' : '';
        });
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

    public static function flushCache(): void
    {
        parent::flushCache();
        self::$asciiCharMaps = null;
        self::$shortcodeEscapeMap = null;
    }
}
