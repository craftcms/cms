<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Class Json
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Json
{
    /**
     * Encodes the given value into a JSON string.
     *
     * @param  mixed  $value  the data to be encoded.
     * @param  int  $options  The encoding options. `JSON_UNESCAPED_UNICODE` is used by default.
     */
    public static function encode(mixed $value, int $options = JSON_UNESCAPED_UNICODE, int $depth = 512): string|false
    {
        return json_encode($value, $options, $depth);
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     *
     * @param  string  $json  the JSON string to be decoded
     * @param  bool  $asArray  whether to return objects in terms of associative arrays.
     * @return mixed the PHP data
     *
     * @throws InvalidArgumentException if there is any decoding error
     */
    public static function decode(mixed $json, bool $asArray = true): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }

        if (! is_string($json)) {
            throw new InvalidArgumentException('Invalid JSON data.');
        }

        try {
            return json_decode($json, $asArray, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid JSON data.');
        }
    }

    /**
     * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
     *
     * @param  mixed  $str  The string to be decoded, if it's valid JSON.
     * @param  bool  $asArray  Whether to return objects in terms of associative arrays.
     * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
     */
    public static function decodeIfJson(mixed $str, bool $asArray = true): mixed
    {
        if (empty($str)) {
            return null;
        }

        if (! Str::isJson($str)) {
            return $str;
        }

        try {
            return json_decode($str, $asArray, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            // Wasn't JSON
            return $str;
        }
    }

    /**
     * Decodes JSON from a given file path.
     *
     * @param  string  $file  the file path
     * @param  bool  $asArray  whether to return objects in terms of associative arrays
     * @return mixed The JSON-decoded file contents
     *
     * @throws InvalidArgumentException if the file doesn’t exist or there was a problem JSON-decoding it
     */
    public static function decodeFromFile(string $file, bool $asArray = true): mixed
    {
        if (! File::exists($file)) {
            throw new InvalidArgumentException("`$file` doesn’t exist.");
        }

        if (is_dir($file)) {
            throw new InvalidArgumentException("`$file` is a directory.");
        }

        try {
            return static::decode(File::get($file), $asArray);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("`$file` doesn’t contain valid JSON.");
        }
    }

    /**
     * Writes out a JSON file for the given value, maintaining its current
     * indentation sequence if the file already exists.
     *
     * @param  string  $path  The file path
     * @param  mixed  $value  the data to be encoded.
     * @param  int  $options  The encoding options. `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT`
     *                        is used by default.
     * @param  string  $defaultIndent  The default indentation sequence to use if the file doesn’t exist
     */
    public static function encodeToFile(
        string $path,
        mixed $value,
        int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        string $defaultIndent = '  ',
    ): void {
        $json = static::encode($value, $options);

        if ($options & JSON_PRETTY_PRINT) {
            if (File::exists($path)) {
                $indent = static::detectIndent(File::get($path));
            } else {
                $indent = $defaultIndent;
            }

            $json = static::reindent($json, $indent);
        }

        File::writeToFile($path, $json."\n");
    }

    /**
     * Detects and returns the indentation sequence used by the given JSON string.
     */
    public static function detectIndent(string $json): string
    {
        if (! preg_match('/^\s*\{\s*[\r\n]+([ \t]+)"/', $json, $match)) {
            return '  ';
        }

        return $match[1];
    }

    /**
     * Re-indents JSON with the given indentation string.
     */
    public static function reindent(string $json, string $indent = '  '): string
    {
        if ($indent !== '    ') {
            return preg_replace_callback('/^ {4,}/m', fn (array $match) => strtr($match[0], ['    ' => $indent]), $json);
        }

        return $json;
    }
}
