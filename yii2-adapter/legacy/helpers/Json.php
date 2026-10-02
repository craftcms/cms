<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Support\Str;
use InvalidArgumentException;

/**
 * Class Json
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use `\CraftCms\Cms\Support\Json` instead.
 */
class Json extends \yii\helpers\Json
{
    /**
     * Returns whether a string value looks like a JSON object or array.
     *
     * @param string $str
     *
     * @return bool
     * @since 3.5.0
     * @deprecated in 6.0.0. Use `\CraftCms\Cms\Support\Str::isJson` instead.
     */
    public static function isJsonObject(string $str): bool
    {
        return Str::isJson($str);
    }

    /**
     * @inheritdoc
     * @param int $options The encoding options. `JSON_UNESCAPED_UNICODE` is used by default.
     */
    public static function encode($value, $options = JSON_UNESCAPED_UNICODE)
    {
        return \CraftCms\Cms\Support\Json::encode($value, $options);
    }

    /**
     * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
     *
     * @param mixed $str The string to be decoded, if it's valid JSON.
     * @param bool $asArray Whether to return objects in terms of associative arrays.
     * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
     */
    public static function decodeIfJson(mixed $str, bool $asArray = true): mixed
    {
        return \CraftCms\Cms\Support\Json::decodeIfJson($str, $asArray);
    }

    /**
     * Decodes JSON from a given file path.
     *
     * @param string $file the file path
     * @param bool $asArray whether to return objects in terms of associative arrays
     * @return mixed The JSON-decoded file contents
     * @throws InvalidArgumentException if the file doesn’t exist or there was a problem JSON-decoding it
     * @since 4.3.5
     */
    public static function decodeFromFile(string $file, bool $asArray = true): mixed
    {
        return \CraftCms\Cms\Support\Json::decodeFromFile($file, $asArray);
    }

    /**
     * Detects and returns the indentation sequence used by the given JSON string.
     *
     * @param string $json
     * @return string
     * @since 5.0.0
     */
    public static function detectIndent(string $json): string
    {
        return \CraftCms\Cms\Support\Json::detectIndent($json);
    }

    /**
     * Writes out a JSON file for the given value, maintaining its current
     * indentation sequence if the file already exists.
     *
     * @param string $path The file path
     * @param mixed $value the data to be encoded.
     * @param int $options The encoding options. `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT`
     * is used by default.
     * @param string $defaultIndent The default indentation sequence to use if the file doesn’t exist
     * @since 5.0.0
     */
    public static function encodeToFile(
        string $path,
        mixed $value,
        int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        string $defaultIndent = '  ',
    ): void {
        \CraftCms\Cms\Support\Json::encodeToFile($path, $value, $options, $defaultIndent);
    }

    /**
     * Re-indents JSON with the given indentation string.
     *
     * @param string $json
     * @param string $indent
     * @return string
     * @since 5.7.0
     */
    public static function reindent(string $json, string $indent = '  '): string
    {
        return \CraftCms\Cms\Support\Json::reindent($json, $indent);
    }
}
